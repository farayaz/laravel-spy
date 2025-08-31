<?php

namespace Farayaz\LaravelSpy\Http\Controllers;

use Carbon\CarbonImmutable;
use Farayaz\LaravelSpy\Models\HttpLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

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

        // Recent activity by day (for sparkline)
        $recentByDay = (clone $base)
            ->selectRaw(
                DB::raw(
                    match (DB::getDriverName()) {
                        'mysql' => "DATE_FORMAT(created_at, '%Y-%m-%d') as bucket",
                        'pgsql' => "to_char(date_trunc('day', created_at), 'YYYY-MM-DD') as bucket",
                        'sqlite' => "strftime('%Y-%m-%d', created_at) as bucket",
                        default => "created_at as bucket",
                    } . ", COUNT(*) as c"
                )
            )
            ->groupBy('bucket')
            ->orderByDesc('bucket')
            ->get();

        return view('spy::dashboard', [
            'period' => $period,
            'from' => $from,
            'total' => $total,
            'count2xx' => $count2xx,
            'count4xx' => $count4xx,
            'count5xx' => $count5xx,
            'count500' => $count500,
            'topFailures' => $topFailures,
            'recentByDay' => $recentByDay,
        ]);
    }
}
