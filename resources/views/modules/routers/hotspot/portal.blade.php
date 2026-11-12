@php
    $safeText = static fn (?string $text): string => str_replace('$', '&#36;', e($text ?? ''));
    $color = sscanf($design['accent'], '#%02x%02x%02x');
    $channels = array_map(static fn (int $channel): float => ($channel /= 255) <= .04045 ? $channel / 12.92 : (($channel + .055) / 1.055) ** 2.4, $color);
    $buttonText = (.2126 * $channels[0] + .7152 * $channels[1] + .0722 * $channels[2]) > .179 ? '#101828' : '#ffffff';
@endphp
<!doctype html>
<html lang="en" data-layout="{{ $design['layout'] }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{!! $safeText($brand) !!} Wi-Fi</title>
    <style>
        *{box-sizing:border-box}
        html{color-scheme:light;--bg:#eff5f4;--card:#fff;--text:#172524;--muted:#475c59;--line:#b9cac7}
        html[data-layout=dark]{color-scheme:dark;--bg:#111c20;--card:#1b282d;--text:#f3faf9;--muted:#bbcdca;--line:#687f7c}
        body{margin:0;min-height:100vh;padding:24px 16px;display:grid;place-items:center;background:var(--bg);color:var(--text);font:16px/1.5 system-ui,sans-serif}
        main{width:min(100%,390px);padding:28px;border:1px solid var(--line);border-radius:22px;background:var(--card);box-shadow:0 12px 38px #0001}
        header{display:flex;align-items:center;gap:12px;font-weight:700;overflow-wrap:anywhere}
        header img,.brand-mark{width:48px;height:48px;object-fit:contain;border-radius:10px;background:#fff}
        .brand-mark{display:grid;place-items:center;color:#155e54;border:1px solid #b9cac7;font-size:22px}
        h1{font-size:clamp(24px,6vw,32px);line-height:1.15;margin:24px 0 8px;overflow-wrap:anywhere}
        p{margin:0 0 18px;color:var(--muted);overflow-wrap:anywhere}
        label{display:block;font-size:.875rem;font-weight:650;margin-top:13px}
        input{display:block;width:100%;height:45px;margin-top:5px;padding:8px 11px;border-radius:9px;border:1px solid var(--line);background:var(--card);color:var(--text);font:inherit}
        button{width:100%;min-height:46px;margin-top:20px;border:0;border-radius:9px;background:var(--accent);color:var(--button-text);font:inherit;font-weight:700}
        button:disabled{opacity:.75}
        a{color:var(--text)}
        :focus-visible{outline:2px solid var(--text);outline-offset:3px}
        .error,.success{border:1px solid currentColor;border-radius:8px;padding:8px 10px;margin:12px 0}
        .trial{margin-top:14px}
    </style>
</head>
<body style="--accent:{{ $design['accent'] }};--button-text:{{ $buttonText }}">
<main>
    <header>
        @if ($logo)
            <img src="{{ $logo }}" alt="">
        @else
            <span class="brand-mark" aria-hidden="true">{!! $safeText(mb_strtoupper(mb_substr($brand, 0, 1))) !!}</span>
        @endif
        <span>{!! $safeText($brand) !!}</span>
    </header>
    <h1 id="portal-headline">{!! $safeText($design['headline']) !!}</h1>
    <p id="portal-message">{!! $safeText($design['message']) !!}</p>
    @if ($preview)
        <p id="portal-error" class="error" hidden>That username or password was incorrect. Try again.</p>
        <p id="portal-success" class="success" hidden>Connected. You can now access the internet.</p>
        <form id="portal-form" inert>
    @else
        $(if error)
            <p class="error" role="alert">$(error)</p>
        $(endif)
        <form id="portal-form" action="$(link-login-only)" method="post">
            <input type="hidden" name="dst" value="$(link-orig)">
            <input type="hidden" name="popup" value="true">
    @endif
        <label for="portal-user">Username</label>
        <input id="portal-user" name="username" autocomplete="username" @unless($preview) value="$(username)" @endunless required>
        <label for="portal-password">Password</label>
        <input id="portal-password" name="password" type="password" autocomplete="current-password" required>
        <button id="portal-button" type="submit" @unless($preview) $(if chap-id) disabled $(endif) @endunless>{!! $safeText($design['button']) !!}</button>
    </form>
    @unless ($preview)
        $(if trial == 'yes')
            <div class="trial"><a href="$(link-login-only)?dst=$(link-orig-esc)&amp;username=T-$(mac-esc)">Start a free trial</a></div>
        $(endif)
        $(if chap-id)
            <script src="md5.js"></script>
            <script>
                const form = document.getElementById('portal-form');
                const password = document.getElementById('portal-password');
                if (typeof hexMD5 === 'function') {
                    form.addEventListener('submit', function (event) {
                        event.preventDefault();
                        password.value = hexMD5('$(chap-id)' + password.value + '$(chap-challenge)');
                        HTMLFormElement.prototype.submit.call(form);
                    });
                    document.getElementById('portal-button').disabled = false;
                }
            </script>
        $(endif)
    @endunless
</main>
</body>
</html>
