{{-- White-label email shell (PRD §15.4). Zero Soluusi brand/link.
     Vars: $brand (via View composer), $title, $slot content. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? $brand }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f5;font-family:Arial,Helvetica,sans-serif;color:#1f2a26;">
    <div style="max-width:560px;margin:0 auto;padding:24px;">
        <div style="background:#ffffff;border-radius:12px;padding:32px;border:1px solid #e5eae8;">
            <div style="font-size:20px;font-weight:700;color:#0d7a5f;margin-bottom:24px;">{{ $brand }}</div>
            {{ $slot }}
            <div style="margin-top:32px;padding-top:16px;border-top:1px solid #e5eae8;font-size:12px;color:#8a948f;">
                Email ini dikirim otomatis oleh {{ $brand }}. Mohon tidak membalas email ini.
            </div>
        </div>
    </div>
</body>
</html>
