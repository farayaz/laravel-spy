<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laravel Spy · Log #{{ $log->id }}</title>
    <style>
        body { margin:0; background:#0b1020; color:#e7eaf3; font:14px/1.5 system-ui,sans-serif; }
        main { max-width:1100px; margin:32px auto; padding:0 16px; }
        a { color:#9bb6ff; }
        .card { background:#141b2d; border:1px solid #1f2942; border-radius:14px; padding:16px; margin-top:16px; }
        h1 { font-size:20px; overflow-wrap:anywhere; }
        h2 { font-size:16px; }
        .meta { display:flex; flex-wrap:wrap; gap:12px; color:#aab2c5; }
        pre { white-space:pre-wrap; overflow-wrap:anywhere; background:#0e1527; padding:12px; border-radius:8px; max-height:500px; overflow:auto; }
        button { background:#263858; color:#e7eaf3; border:0; border-radius:6px; padding:6px 10px; cursor:pointer; }
    </style>
</head>
<body><main>
    @if(!request()->boolean('embed'))<a href="{{ route('spy.dashboard.logs.index') }}">← HTTP logs</a>@endif
    <h1>{{ $log->method }} {{ $log->url }}</h1>
    <div class="meta"><span>{{ $log->created_at?->format('Y-m-d H:i:s') }}</span><span>Status: {{ $log->status === 0 ? 'Connection error' : ($log->status ?? 'Pending') }}</span><span>Duration: {{ $log->duration_ms === null ? '—' : $log->duration_ms . ' ms' }}</span></div>
    @foreach(['Request headers' => $log->request_headers, 'Request body' => $log->request_body, 'Response headers' => $log->response_headers, 'Response body' => $log->response_body] as $label => $data)
        <section class="card"><h2>{{ $label }}</h2>
            @if($data === null)
                <p>No data</p>
            @else
                <button type="button" onclick="navigator.clipboard.writeText(this.nextElementSibling.textContent)">Copy</button>
                <pre>{{ is_array($data) ? json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) : $data }}</pre>
            @endif
        </section>
    @endforeach
</main></body>
</html>
