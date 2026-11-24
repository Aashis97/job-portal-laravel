<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>{{ $heading }}</title></head>
<body style="margin:0;padding:24px;background:#f1f5f9;font-family:Arial,sans-serif;color:#1e293b">
    <main style="max-width:600px;margin:0 auto;padding:32px;background:#fff;border-radius:12px">
        <h1 style="margin:0 0 16px;color:#4f46e5;font-size:24px">{{ $heading }}</h1>
        <p style="font-size:15px;line-height:1.6">{{ $messageBody }}</p>
        @if($actionLabel && $actionUrl)
            <p style="margin:28px 0"><a href="{{ $actionUrl }}" style="display:inline-block;padding:12px 20px;border-radius:8px;background:#6d28d9;color:#fff;text-decoration:none;font-weight:bold">{{ $actionLabel }}</a></p>
            <p style="font-size:12px;line-height:1.5;color:#64748b">If the button does not work, copy and paste this link into your browser:<br><a href="{{ $actionUrl }}" style="color:#4f46e5;word-break:break-all">{{ $actionUrl }}</a></p>
        @endif
        <p style="margin-top:28px;font-size:12px;color:#64748b">Online Job Portal</p>
    </main>
</body>
</html>
