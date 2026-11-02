<?php

namespace Farayaz\LaravelSpy\Http\Controllers;

use Carbon\CarbonImmutable;
use Farayaz\LaravelSpy\Models\HttpLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LaravelSpyController extends Controller
{
    public function chartScript()
    {
        return response()->file(__DIR__ . '/../../../resources/assets/apexcharts.min.js', [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    public function index(Request $request)
    {
        // Period: 24h | 7d | 30d | all (default 24h)
        $period = $request->string('period')->value() ?: '24h';
        if (! in_array($period, ['24h', '7d', '30d', 'all'], true)) {
            $period = '24h';
        }
        $from = match ($period) {
            '7d' => CarbonImmutable::now()->subDays(7),
            '30d' => CarbonImmutable::now()->subDays(30),
            'all' => null,
            default => CarbonImmutable::now()->subDay(),
        };

        $base = HttpLog::query();
        if ($from !== null) {
            $base->where('created_at', '>=', $from);
        }

        $view = $request->routeIs('spy.dashboard.logs.index') ? 'logs' : 'analytics';
        if ($view === 'analytics') {
            $chartScriptVersion = substr(hash_file('sha256', __DIR__ . '/../../../resources/assets/apexcharts.min.js'), 0, 12);
            $driver = (new HttpLog)->getConnection()->getDriverName();
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
        } else {
            $chartScriptVersion = null;
            $total = $count2xx = $count4xx = $count5xx = $count500 = 0;
            $topFailures = $chart = collect();
        }

        if ($view === 'logs') {
            $logsQuery = clone $base;
            $url = trim((string) $request->query('url', ''));
            $method = strtoupper(trim((string) $request->query('method', '')));
            $status = (string) $request->query('status', '');
            $minDuration = $request->query('min_duration');

            if ($url !== '') {
                $pattern = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $url);
                $logsQuery->whereRaw("url LIKE ? ESCAPE '!'", ['%' . $pattern . '%']);
            }
            if (in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'], true)) {
                $logsQuery->where('method', $method);
            }
            if (in_array($status, ['0', '2xx', '3xx', '4xx', '5xx'], true)) {
                if ($status === '0') {
                    $logsQuery->where('status', 0);
                } else {
                    $hundreds = (int) $status[0];
                    $logsQuery->whereBetween('status', [$hundreds * 100, $hundreds * 100 + 99]);
                }
            }
            if (is_numeric($minDuration) && (float) $minDuration >= 0) {
                $logsQuery->where('duration_ms', '>=', (float) $minDuration);
            }

            $perPage = in_array((int) $request->query('per_page'), [10, 20, 50], true)
                ? (int) $request->query('per_page') : 20;
            $sort = $request->query('sort') === 'oldest' ? 'oldest' : 'newest';
            $logs = $logsQuery->select(['id', 'created_at', 'method', 'url', 'status', 'duration_ms'])
                ->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc')
                ->orderBy('id', $sort === 'oldest' ? 'asc' : 'desc')
                ->paginate($perPage)->withQueryString();
        } else {
            $url = $method = $status = '';
            $minDuration = null;
            $perPage = 20;
            $sort = 'newest';
            $logs = null;
        }

        return view('spy::dashboard', compact(
            'period',
            'from',
            'total',
            'count2xx',
            'count4xx',
            'count5xx',
            'count500',
            'topFailures',
            'chart',
            'chartScriptVersion',
            'logs',
            'url',
            'method',
            'status',
            'minDuration',
            'view',
            'perPage',
            'sort'
        ));
    }

    public function show(int $log)
    {
        $log = HttpLog::findOrFail($log);

        return view('spy::log', compact('log'));
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
