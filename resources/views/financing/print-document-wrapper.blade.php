<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { color: #1e293b; font-family: Arial, sans-serif; font-size: 12px; line-height: 1.5; margin: 24px; }
        table { border-collapse: collapse; width: 100%; }
        td, th { border: 1px solid #cbd5e1; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; }
        .meta td:first-child { color: #475569; width: 180px; }
        .signature { display: block; margin-top: 48px; }
        .line { border-top: 1px solid #475569; padding-top: 4px; }
    </style>
</head>
<body>
    {!! $content !!}
</body>
</html>