<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Laravel Spy · Dashboard</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=jetbrains-mono:300" rel="stylesheet" />

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
            font-family: 'JetBrains Mono', monospace !important;
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
    </style>
</head>

<body>
    <div class="wrap">
        <header>
            <h1>Laravel Spy · Dashboard</h1>
            <form method="get">
                <label class="muted">
                    Period
                    <select name="period" onchange="this.form.submit()">
                        <option value="24h" {{ $period === '24h' ? 'selected' : '' }}>Last 24h</option>
                        <option value="7d" {{ $period === '7d' ? 'selected' : '' }}>Last 7 days</option>
                        <option value="30d" {{ $period === '30d' ? 'selected' : '' }}>Last 30 days</option>
                    </select>
                </label>
            </form>
        </header>

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
                        From {{ $from->format('Y-m-d H:i') }} to now
                    </div>

                    <div id="chart" style="width: 100%;height: 100px"></div>
                </div>
            </div>

            <div class="row">
                <div class="card" style="flex:1">
                    <h3 class="muted" style="margin:0 0 8px 0; font-size:13px;">Top failing URLs (5xx)</h3>

                    <div class="muted" style="margin-top:6px; font-size:12px;">
                        From {{ $from->format('Y-m-d H:i') }} to now
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>URL</th>
                                <th>Failures</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topFailures as $row)
                                <tr>
                                    <td>{{ $row->url }}</td>
                                    <td>{{ $row->failures }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="muted" style="text-align: center;">No 5xx requests in the
                                        selected period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </section>

        <footer>
            Built
            <a href="https://github.com/farayaz/laravel-spy" target="_blank" rel="noreferrer">
                Laravel Spy
            </a>
            with ❤️
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

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
                height: '500px',
                background: '#141b2d',
                zoom: {
                    enabled: false
                }
            },
            stroke: {
                curve: 'smooth',
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
</body>

</html>
