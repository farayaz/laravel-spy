<?php

namespace Farayaz\LaravelSpy\Http\Controllers;

use Carbon\CarbonImmutable;
use Farayaz\LaravelSpy\Models\HttpLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LaravelSpyController extends Controller
{
    public function index(Request $request)
    {
        // Period: 24h | 7d | 30d (default 24h)
        $period = $request->string('period')->value() ?: '24h';
        $from = match ($period) {
            '7d' => CarbonImmutable::now()->subDays(7),
            '30d' => CarbonImmutable::now()->subDays(30),
            default => CarbonImmutable::now()->subDay(),
        };

        $driver = (new HttpLog)->getConnection()->getDriverName();

        $base = HttpLog::query()->whereDate('created_at', '>=', $from);

        $total = (clone $base)->count();
        $count2xx = (clone $base)->whereBetween('status', [200, 299])->count();
        $count4xx = (clone $base)->whereBetween('status', [400, 499])->count();
        $count5xx = (clone $base)->where('status', '>=', 500)->count();
        $count500 = (clone $base)->where('status', 500)->count();

        // Top failing URLs (500+)
        $topFailures = (clone $base)
            ->selectRaw('url, COUNT(*) as failures')
            ->where('status', '>=', 500)
            ->groupBy('url')
            ->orderByDesc('failures')
            ->limit(10)
            ->get();

        $chart = (clone $base)
            ->selectRaw(
                $this->getChartSelectRaw($driver, $period)
            )
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return view('spy::dashboard', compact(
            'period',
            'from',
            'total',
            'count2xx',
            'count4xx',
            'count5xx',
            'count500',
            'topFailures',
            'chart'
        ));
    }

    /**
     * Get the appropriate SELECT RAW query based on database driver
     */
    private function getChartSelectRaw(string $driver, string $period): string
    {
        return match ($driver) {
            'sqlite' => match ($period) {
                '24h' => "strftime('%Y-%m-%d %H:00', created_at) as bucket, COUNT(*) as c",
                default => "strftime('%Y-%m-%d', created_at) as bucket, COUNT(*) as c",
            },
            'pgsql' => match ($period) {
                '24h' => "to_char(created_at, 'YYYY-MM-DD HH24:00') as bucket, COUNT(*) as c",
                default => "to_char(created_at, 'YYYY-MM-DD') as bucket, COUNT(*) as c",
            },
            default => match ($period) { // MySQL and others
                '24h' => "DATE_FORMAT(created_at, '%Y-%m-%d %H:00') as bucket, COUNT(*) as c",
                default => 'DATE(created_at) as bucket, COUNT(*) as c',
            },
        };
    }
}
