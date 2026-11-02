<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Laravel Spy · {{ $view === 'analytics' ? 'Analytics' : 'HTTP logs' }}</title>

    <style>
        :root {
            --bg: #0b1020;
            --card: #141b2d;
            --ink: #e7eaf3;
            --muted: #aab2c5;
            --ok: #19b38a;
            --warn: #e6b800;
            --bad: #e44;
        }

        * {
            font-family: ui-monospace, SFMono-Regular, Consolas, 'Liberation Mono', monospace !important;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font: 14px/1.4 system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;
            color: var(--ink);
            background: linear-gradient(180deg, #0b1020, #101935);
        }

        .wrap {
            max-width: 1100px;
            margin: 32px auto;
            padding: 0 16px;
        }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        h1 {
            font-size: 20px;
            margin: 0;
            letter-spacing: .3px;
        }

        .card {
            background: var(--card);
            border: 1px solid #1f2942;
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, .25);
        }

        .grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(5, 1fr);
        }

        .kpi {
            text-align: center;
            padding: 16px;
            border-radius: 12px;
        }

        .kpi small {
            color: var(--muted);
            display: block;
            margin-top: 6px;
        }

        .kpi b {
            font-size: 22px;
            display: block;
        }

        .kpi.ok b {
            color: var(--ok);
        }

        .kpi.warn b {
            color: var(--warn);
        }

        .kpi.bad b {
            color: var(--bad);
        }

        .row {
            display: flex;
            gap: 12px;
            margin-top: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #1f2942;
            text-align: left;
        }

        th {
            color: var(--muted);
            font-weight: 600;
        }

        .muted {
            color: var(--muted);
        }

        select {
            background: #0e1527;
            color: var(--ink);
            border: 1px solid #1f2942;
            border-radius: 10px;
            padding: 8px 10px;
        }

        .spark {
            display: flex;
            gap: 2px;
            align-items: flex-end;
            height: 48px;
        }

        .spark i {
            display: block;
            width: 6px;
            background: #415aab;
            border-radius: 2px 2px 0 0;
        }

        footer {
            margin-top: 24px;
            color: var(--muted);
            text-align: center;
        }

        a {
            color: #9bb6ff;
            text-decoration: none;
        }
        input { background:#0e1527; color:var(--ink); border:1px solid #1f2942; border-radius:10px; padding:8px 10px; }
        .filters { display:flex; flex-wrap:wrap; gap:8px; margin:12px 0; }
        .table-wrap { overflow-x:auto; }
        td.url { max-width:480px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .pagination { display:flex; justify-content:space-between; margin-top:14px; }
        .tabs { display:flex; gap:8px; margin:16px 0; }
        .tabs a { padding:9px 16px; border:1px solid #293757; border-radius:9px; }
        .tabs a.active { background:#324d83; color:white; }
        .toolbar { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; }
        .toolbar form { display:flex; gap:8px; }
        button { background:#324d83; color:white; border:0; border-radius:9px; padding:8px 12px; cursor:pointer; }
        .badge { display:inline-block; border-radius:999px; padding:3px 8px; background:#24314c; }
        .badge.ok { color:var(--ok); } .badge.warn { color:var(--warn); } .badge.bad { color:var(--bad); }
        dialog { width:min(760px,96vw); height:100vh; max-height:100vh; margin:0 0 0 auto; padding:0; border:1px solid #293757; border-radius:14px 0 0 14px; background:#141b2d; color:var(--ink); }
        dialog::backdrop { background:rgba(0,0,0,.75); }
        dialog iframe { border:0; width:100%; height:calc(100% - 52px); }
        .dialog-head { display:flex; justify-content:space-between; padding:10px 16px; align-items:center; }
        @media(max-width:750px) { .grid { grid-template-columns:repeat(2,1fr); } header { align-items:flex-start; gap:12px; flex-direction:column; } .wrap { margin:16px auto; } }
    </style>
</head>

<body>
    <div class="wrap">
        <header>
            <h1>Laravel Spy · {{ $view === 'analytics' ? 'Analytics' : 'HTTP logs' }}</h1>
            <form method="get">
                <input type="hidden" name="url" value="{{ $url }}">
                <input type="hidden" name="method" value="{{ $method }}">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="hidden" name="min_duration" value="{{ $minDuration }}">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">
                <label class="muted">
                    Period
                    <select name="period" onchange="this.form.submit()">
                        <option value="24h" {{ $period === '24h' ? 'selected' : '' }}>Last 24h</option>
                        <option value="7d" {{ $period === '7d' ? 'selected' : '' }}>Last 7 days</option>
                        <option value="30d" {{ $period === '30d' ? 'selected' : '' }}>Last 30 days</option>
                        <option value="all" {{ $period === 'all' ? 'selected' : '' }}>All time</option>
                    </select>
                </label>
            </form>
        </header>

        <nav class="tabs" aria-label="Dashboard views">
            <a class="{{ $view === 'analytics' ? 'active' : '' }}" href="{{ route('spy.dashboard.index', ['period' => $period]) }}">Analytics</a>
            <a class="{{ $view === 'logs' ? 'active' : '' }}" href="{{ route('spy.dashboard.logs.index', ['period' => $period]) }}">HTTP logs</a>
        </nav>

        @if($view === 'analytics')
        <section class="card">
            <div class="grid">
                <div class="kpi">
                    <div class="card">
                        <b>{{ number_format($total) }}</b><small>Total</small>
                    </div>
                </div>
                <div class="kpi ok">
                    <div class="card">
                        <b>{{ number_format($count2xx) }}</b><small>2xx</small>
                    </div>
                </div>
                <div class="kpi warn">
                    <div class="card">
                        <b>{{ number_format($count4xx) }}</b><small>4xx</small>
                    </div>
                </div>

                <div class="kpi bad">
                    <div class="card">
                        <b>{{ number_format($count5xx) }}</b><small>5xx</small>
                    </div>
                </div>

                <div class="kpi bad">
                    <div class="card">
                        <b>{{ number_format($count500) }}</b><small>HTTP 500</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="card" style="flex:1">
                    <h3 class="muted" style="margin:0 0 8px 0; font-size:13px;">Requests over time</h3>

                    <div class="muted" style="margin-top:6px; font-size:12px;">
                        {{ $from ? 'From ' . $from->format('Y-m-d H:i') . ' to now' : 'All time' }}
                    </div>

                    <div id="chart" style="width: 100%; height: 220px"></div>
                </div>
            </div>

            <div class="row">
                <div class="card" style="flex:1">
                    <h3 class="muted" style="margin:0 0 8px 0; font-size:13px;">Top failing URLs (5xx)</h3>
                    <table><thead><tr><th>URL</th><th>Failures</th></tr></thead><tbody>
                    @forelse($topFailures as $row)
                        <tr><td>{{ $row->url }}</td><td>{{ $row->failures }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="muted">No 5xx requests in this period.</td></tr>
                    @endforelse
                    </tbody></table>
                </div>
            </div>
        </section>
        @else
        <section class="card">

            <div class="row">
                <div class="card" style="flex:1; min-width:0">
                    <div class="toolbar"><div><h2 style="margin:0">HTTP logs</h2><small class="muted">{{ number_format($logs->total()) }} matching requests</small></div>
                        <form method="get"><input type="hidden" name="period" value="{{ $period }}"><input type="hidden" name="url" value="{{ $url }}"><input type="hidden" name="method" value="{{ $method }}"><input type="hidden" name="status" value="{{ $status }}"><input type="hidden" name="min_duration" value="{{ $minDuration }}">
                            <select name="sort" aria-label="Sort order" onchange="this.form.submit()"><option value="newest" @selected($sort === 'newest')>Newest first</option><option value="oldest" @selected($sort === 'oldest')>Oldest first</option></select>
                            <select name="per_page" aria-label="Rows per page" onchange="this.form.submit()">@foreach([10,20,50] as $size)<option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} per page</option>@endforeach</select>
                        </form></div>
                    <form method="get" class="filters">
                        <input type="hidden" name="period" value="{{ $period }}">
                        <input type="hidden" name="sort" value="{{ $sort }}">
                        <input type="hidden" name="per_page" value="{{ $perPage }}">
                        <input name="url" value="{{ $url }}" placeholder="Search URL" aria-label="Search URL">
                        <select name="method" aria-label="Method">
                            <option value="">All methods</option>
                            @foreach(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'] as $option)
                                <option value="{{ $option }}" @selected($method === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                        <select name="status" aria-label="Status">
                            <option value="">All statuses</option>
                            @foreach(['0' => 'Connection error', '2xx' => '2xx', '3xx' => '3xx', '4xx' => '4xx', '5xx' => '5xx'] as $value => $label)
                                <option value="{{ $value }}" @selected($status === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="min_duration" min="0" value="{{ $minDuration }}" placeholder="Min ms" aria-label="Minimum duration in milliseconds">
                        <button type="submit">Filter</button>
                        <a href="{{ route('spy.dashboard.logs.index', ['period' => $period]) }}" style="padding:8px">Clear</a>
                    </form>
                    <div class="table-wrap"><table>
                        <thead><tr><th>Time</th><th>Method</th><th>URL</th><th>Status</th><th>Duration</th></tr></thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td>{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                                    <td>{{ $log->method }}</td>
                                    <td class="url" title="{{ $log->url }}"><a class="detail-link" href="{{ route('spy.dashboard.logs.show', $log) }}">{{ $log->url }}</a></td>
                                    <td><span class="badge {{ $log->status >= 500 || $log->status === 0 ? 'bad' : ($log->status >= 400 ? 'warn' : 'ok') }}">{{ $log->status === 0 ? 'Error' : ($log->status ?? 'Pending') }}</span></td>
                                    <td>{{ $log->duration_ms === null ? '—' : $log->duration_ms . ' ms' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="muted" style="text-align:center">No logs match these filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table></div>
                    <div class="pagination">
                        <span>{{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }}</span>
                        <span>Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}</span>
                        <span>@if($logs->currentPage() > 1)<a href="{{ $logs->url(1) }}">First</a> · <a href="{{ $logs->previousPageUrl() }}">Previous</a> · @endif
                            @for($page = max(1, $logs->currentPage()-2); $page <= min($logs->lastPage(), $logs->currentPage()+2); $page++)
                                <a href="{{ $logs->url($page) }}" @if($page === $logs->currentPage()) aria-current="page" @endif>{{ $page }}</a>
                            @endfor
                            @if($logs->nextPageUrl()) · <a href="{{ $logs->nextPageUrl() }}">Next</a> · <a href="{{ $logs->url($logs->lastPage()) }}">Last</a>@endif</span>
                    </div>
                </div>
            </div>

        </section>
        @endif

        <dialog id="log-detail"><div class="dialog-head"><strong>HTTP log details</strong><button type="button" onclick="this.closest('dialog').close()">Close</button></div><iframe title="HTTP log details"></iframe></dialog>

        <footer>
            Built
            <a href="https://github.com/farayaz/laravel-spy" target="_blank" rel="noreferrer">
                Laravel Spy
            </a>
            with ❤️
        </footer>
    </div>

    @if($view === 'analytics')
    <script src="{{ route('spy.dashboard.chart-script', ['v' => $chartScriptVersion]) }}"></script>

    <script>
        options = {
            theme: {
                mode: 'dark',
            },
            tooltip: {
                theme: 'dark',
            },
            chart: {
                type: 'line',
                height: 220,
                background: '#141b2d',
                zoom: {
                    enabled: false
                }
            },
            stroke: {
                curve: 'straight',
            },
            yaxis: {
                min: 0,
                forceNiceScale: true,
                labels: {
                    formatter: function (value) { return Math.round(value).toString(); }
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false
                }
            },
            series: [{
                data: [
                    @foreach ($chart as $b)
                        {
                            x: '{{ $b->bucket }}',
                            y: {{ $b->c }}
                        },
                    @endforeach
                ]
            }]
        }

        var chart = new ApexCharts(document.querySelector("#chart"), options);

        chart.render();
    </script>
    @endif
    <script>
        var detailDialog = document.getElementById('log-detail');
        detailDialog.addEventListener('click', function(event) {
            if (event.target !== detailDialog) return;
            var bounds = detailDialog.getBoundingClientRect();
            if (event.clientX < bounds.left || event.clientX > bounds.right ||
                event.clientY < bounds.top || event.clientY > bounds.bottom) {
                detailDialog.close();
            }
        });
        detailDialog.addEventListener('cancel', function() {
            detailDialog.close();
        });
        document.querySelectorAll('.detail-link').forEach(function(link) {
            link.addEventListener('click', function(event) {
                var dialog = document.getElementById('log-detail');
                if (!dialog || !dialog.showModal) return;
                event.preventDefault();
                dialog.querySelector('iframe').src = link.href + '?embed=1';
                dialog.showModal();
            });
        });
    </script>
</body>

</html>
