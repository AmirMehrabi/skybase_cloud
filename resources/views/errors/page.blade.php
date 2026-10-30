@php
    $status = (int) ($status ?? 500);
    [$title, $description, $label] = match ($status) {
        401 => ['Sign in required', 'Sign in to continue to your SkyBase workspace.', 'Authentication'],
        403 => ['Access denied', 'You do not have permission to open this page. If you believe this is an error, contact your system administrator.', 'Permission'],
        404 => ['Page not found', 'The page may have moved, or the address may be incorrect.', 'Not found'],
        419 => ['Session expired', 'Your session expired for security. Return to the page and try again.', 'Session'],
        429 => ['Too many requests', 'Please wait a moment before trying again.', 'Rate limit'],
        500 => ['Something went wrong', 'SkyBase could not complete this request. Please try again shortly.', 'Server error'],
        503 => ['Temporarily unavailable', 'SkyBase is temporarily unavailable. Please try again shortly.', 'Maintenance'],
        default => [$status >= 500 ? 'Something went wrong' : 'Request could not be completed', $status >= 500 ? 'SkyBase could not complete this request. Please try again shortly.' : 'Check the address or return to your workspace.', 'SkyBase'],
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0d2f35">
    <title>{{ $status }} · {{ $title }} · SkyBase</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f6f1e8; color: #17211f; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: radial-gradient(circle at 12% 10%, rgba(34,197,94,.12), transparent 30%), radial-gradient(circle at 90% 90%, rgba(245,197,66,.16), transparent 32%), #f6f1e8; }
        main { width: min(100%, 620px); overflow: hidden; border: 1px solid rgba(13,47,53,.12); border-radius: 28px; background: rgba(255,255,255,.94); box-shadow: 0 32px 90px rgba(13,47,53,.14); }
        header { display: flex; align-items: center; gap: 12px; padding: 22px 28px; border-bottom: 1px solid rgba(13,47,53,.1); }
        .mark { display: grid; width: 38px; height: 38px; place-items: center; border-radius: 12px; background: #0d2f35; color: #f5c542; font-weight: 800; }
        .brand { color: #0d2f35; font-size: 18px; font-weight: 750; letter-spacing: -.03em; }
        section { padding: clamp(30px, 8vw, 56px) clamp(24px, 7vw, 48px); }
        .eyebrow { display: inline-flex; align-items: center; gap: 8px; padding: 7px 12px; border: 1px solid rgba(13,47,53,.12); border-radius: 999px; background: #fbf7ed; color: #285653; font-size: 12px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        .code { margin: 24px 0 4px; color: #0d2f35; font-size: clamp(66px, 17vw, 104px); font-weight: 800; letter-spacing: -.09em; line-height: .95; }
        h1 { margin: 18px 0 10px; color: #17211f; font-size: clamp(25px, 6vw, 34px); letter-spacing: -.04em; }
        p { max-width: 460px; margin: 0; color: #58645f; font-size: 16px; line-height: 1.7; }
        nav { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 30px; }
        a { display: inline-flex; min-height: 46px; align-items: center; justify-content: center; padding: 0 18px; border: 1px solid #0d2f35; border-radius: 14px; background: #0d2f35; color: white; font-size: 14px; font-weight: 700; text-decoration: none; transition: background .15s ease, transform .15s ease; }
        a:hover { transform: translateY(-1px); background: #123f3d; }
        a.secondary { background: white; color: #0d2f35; }
        a.secondary:hover { background: #fbf7ed; }
        footer { padding: 16px 28px; border-top: 1px solid rgba(13,47,53,.08); color: #78827e; font-size: 12px; }
        @media (max-width: 480px) { body { padding: 14px; } header { padding: 18px 20px; } section { padding: 32px 22px; } nav { flex-direction: column; } nav a { width: 100%; } }
    </style>
</head>
<body>
<main>
    <header><span class="mark" aria-hidden="true">S</span><span class="brand">SkyBase</span></header>
    <section>
        <span class="eyebrow">{{ $label }}</span>
        <div class="code" aria-label="Error {{ $status }}">{{ $status }}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $description }}</p>
        <nav aria-label="Error page actions">
            <a href="{{ auth()->check() ? url('/dashboard') : url('/') }}">{{ auth()->check() ? 'Return to dashboard' : 'Back to SkyBase' }}</a>
            @if(! auth()->check())<a class="secondary" href="{{ url('/auth/login') }}">Sign in</a>@endif
        </nav>
    </section>
    <footer>SkyBase · ISP operations platform</footer>
</main>
</body>
</html>
