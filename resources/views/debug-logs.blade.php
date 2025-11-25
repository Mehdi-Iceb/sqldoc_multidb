<!DOCTYPE html>
<html>
<head>
    <title>Debug Logs</title>
    <style>
        body { background: #1a1a1a; color: #0f0; font-family: monospace; padding: 20px; }
        pre { white-space: pre-wrap; word-wrap: break-word; font-size: 12px; }
        .error { color: #f00; }
        .warning { color: #fa0; }
        .info { color: #0af; }
    </style>
</head>
<body>
    <h1>Last 500 log lines</h1>
    <pre>{!! nl2br(e($logs)) !!}</pre>
</body>
</html>
```

### 2. Connectez-vous puis allez sur
```
http://iceb.test-sqlinfo.io/debug-logs