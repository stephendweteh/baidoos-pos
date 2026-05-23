<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1a2236">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Baidoos POS">
    <title>Baidoos POS — Login</title>
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icons/icon-192.png') }}">
    <link rel="apple-touch-icon" sizes="192x192" href="{{ asset('icons/icon-192.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #1a2236; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { background: #fff; border-radius: 14px; padding: 2.5rem; width: 100%; max-width: 400px; box-shadow: 0 8px 32px rgba(0,0,0,.25); }
        .brand-logo { font-size: 2rem; color: #3b82f6; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="text-center mb-4">
        <div class="brand-logo"><i class="bi bi-shop"></i></div>
        <h4 class="fw-bold mt-2 mb-0">Baidoos POS</h4>
        <p class="text-muted" style="font-size:.85rem">Sign in to your account</p>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label fw-semibold">Email</label>
            <input type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   placeholder="you@example.com" autofocus required>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Password</label>
            <div class="input-group">
                <input type="password" name="password" id="passwordInput"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="••••••••" required>
                <button type="button" class="btn btn-outline-secondary" id="togglePasswordBtn" aria-label="Show password">
                    <i class="bi bi-eye" id="togglePasswordIcon"></i>
                </button>
            </div>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" name="remember" id="remember">
            <label class="form-check-label" for="remember">Keep me signed in</label>
        </div>

        <button type="submit" class="btn btn-primary w-100 fw-semibold">
            Sign In
        </button>
    </form>
</div>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        const passwordInput = document.getElementById('passwordInput');
        const toggleButton = document.getElementById('togglePasswordBtn');
        const toggleIcon = document.getElementById('togglePasswordIcon');

        if (!passwordInput || !toggleButton || !toggleIcon) {
            return;
        }

        toggleButton.addEventListener('click', function () {
            const shouldShow = passwordInput.type === 'password';
            passwordInput.type = shouldShow ? 'text' : 'password';
            toggleButton.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
            toggleIcon.className = shouldShow ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    })();

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register("{{ asset('sw.js') }}").catch(function () {
                // Ignore service worker registration errors.
            });
        });
    }
</script>
</body>
</html>
