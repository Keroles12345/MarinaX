<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Staradmin</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background:
            radial-gradient(circle at top left, #6672e8 0%, transparent 40%),
            radial-gradient(circle at bottom right, #2a34a8 0%, transparent 40%),
            #3b49df;
        padding: 20px;
    }

    .login-wrapper {
        width: 100%;
        max-width: 1050px;
        min-height: 620px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        background: #ffffff;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 25px 70px rgba(20, 24, 90, 0.35);
    }

    /* Left Side */
    .login-info {
        position: relative;
        padding: 60px;
        color: white;
        background: linear-gradient(145deg, #4c58e6, #22299b);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        overflow: hidden;
    }

    .login-info::before {
        content: "";
        position: absolute;
        width: 280px;
        height: 280px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
        top: -100px;
        right: -100px;
    }

    .login-info::after {
        content: "";
        position: absolute;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.06);
        bottom: -90px;
        left: -90px;
    }

    .brand {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .brand-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.15);
        font-size: 22px;
        font-weight: bold;
    }

    .brand-name {
        font-size: 22px;
        font-weight: 700;
        letter-spacing: 0.3px;
    }

    .info-content { position: relative; z-index: 1; }

    .info-content .badge {
        display: inline-block;
        padding: 7px 13px;
        border-radius: 50px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 20px;
        letter-spacing: 0.5px;
    }

    .info-content h1 { font-size: 42px; line-height: 1.15; margin-bottom: 18px; }

    .info-content p {
        color: rgba(255, 255, 255, 0.75);
        font-size: 15px;
        line-height: 1.8;
        max-width: 400px;
    }

    .features {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .feature {
        display: flex;
        align-items: center;
        gap: 12px;
        color: rgba(255, 255, 255, 0.9);
        font-size: 14px;
    }

    .feature-icon {
        width: 26px;
        height: 26px;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 700;
        flex-shrink: 0;
    }

    /* Right Side */
    .login-form-container {
        padding: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #ffffff;
    }

    .login-form { width: 100%; max-width: 390px; }

    .form-header { margin-bottom: 35px; }

    .form-header h2 { font-size: 30px; color: #111827; margin-bottom: 10px; }

    .form-header p { color: #6b7280; font-size: 14px; }

    .form-group { margin-bottom: 20px; }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #374151;
        font-size: 13px;
        font-weight: 600;
    }

    .input-wrapper { position: relative; }

    .input-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 16px;
    }

    .form-input {
        width: 100%;
        height: 52px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 0 45px 0 45px;
        font-size: 14px;
        color: #111827;
        outline: none;
        background: #f9fafb;
        transition: all 0.2s ease;
    }

    .form-input::placeholder { color: #9ca3af; }

    .form-input:focus {
        background: white;
        border-color: #3b49df;
        box-shadow: 0 0 0 4px rgba(59, 73, 223, 0.10);
    }

    .password-toggle {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: transparent;
        cursor: pointer;
        font-size: 18px;
        color: #9ca3af;
        padding: 5px;
    }

    .password-toggle:hover { color: #3b49df; }

    .form-options {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        font-size: 13px;
    }

    .remember-me {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #6b7280;
        cursor: pointer;
    }

    .remember-me input { accent-color: #3b49df; width: 15px; height: 15px; cursor: pointer; }

    .forgot-link { color: #3b49df; font-weight: 600; text-decoration: none; }

    .forgot-link:hover { text-decoration: underline; }

    .error-message { margin-top: 7px; color: #dc2626; font-size: 12px; }

    .general-errors {
        padding: 12px 14px;
        border-radius: 10px;
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #b91c1c;
        margin-bottom: 20px;
        font-size: 13px;
    }

    .login-button {
        width: 100%;
        height: 52px;
        border: none;
        border-radius: 12px;
        background: linear-gradient(135deg, #3b49df, #2a34a8);
        color: white;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 8px 20px rgba(59, 73, 223, 0.28);
    }

    .login-button:hover { transform: translateY(-1px); box-shadow: 0 12px 25px rgba(59, 73, 223, 0.38); }

    .login-button:active { transform: translateY(0); }

    .login-button:disabled { cursor: not-allowed; opacity: 0.8; }

    .button-loader { display: flex; align-items: center; justify-content: center; gap: 10px; }

    .spinner {
        width: 18px;
        height: 18px;
        border: 3px solid rgba(255, 255, 255, 0.4);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.7s linear infinite;
    }

    @keyframes spin { to { transform: rotate(360deg); } }

    .security-note {
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #f3f4f6;
        text-align: center;
        color: #9ca3af;
        font-size: 11px;
    }

    /* Responsive */
    @media (max-width: 800px) {
        .login-wrapper { grid-template-columns: 1fr; max-width: 480px; }
        .login-info { display: none; }
        .login-form-container { padding: 45px 30px; }
    }

    @media (max-width: 400px) {
        .login-form-container { padding: 35px 20px; }
        .form-header h2 { font-size: 26px; }
    }
</style>
</head>
<body>
    <div style="position:absolute;top:20px;right:20px;z-index:1">@include('shared.language-switcher')</div>

<div class="login-wrapper">

    <!-- Left Side -->
    <div class="login-info">

        <div class="brand">
            <div class="brand-icon">S</div>
            <div class="brand-name">Staradmin</div>
        </div>

        <div class="info-content">
            <span class="badge">MANAGEMENT SYSTEM</span>
            <h1>Welcome<br>Back.</h1>
            <p>
                Access your workspace, manage your tasks,
                and stay connected with your team from one secure place.
            </p>
        </div>

        <div class="features">
            <div class="feature"><div class="feature-icon">✓</div> Secure dashboard access</div>
            <div class="feature"><div class="feature-icon">✓</div> Department-based workspace</div>
            <div class="feature"><div class="feature-icon">✓</div> Centralized management</div>
        </div>

    </div>

    <!-- Right Side -->
    <div class="login-form-container">

        <div class="login-form">

            <div class="form-header">
                <h2>Login</h2>
                <p>Enter your credentials to access your dashboard.</p>
            </div>

            @if ($errors->any())
                <div class="general-errors">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('admin.login.handle') }}" method="POST" autocomplete="on">

                @csrf

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email Address</label>

                    <div class="input-wrapper">
                        <span class="input-icon">✉</span>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            class="form-input"
                            placeholder="Enter your email"
                            value="{{ old('email') }}"
                            autocomplete="username"
                            required
                        >
                    </div>

                    @error('email')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password</label>

                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="form-input"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" id="togglePassword" class="password-toggle" aria-label="Show password">👁</button>
                    </div>

                    @error('password')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                        Remember me
                    </label>
                    @if(Route::has('admin.password.request'))
                        <a href="{{ route('admin.password.request') }}" class="forgot-link">Forgot password?</a>
                    @endif
                </div>

                <button type="submit" class="login-button" id="loginButton">
                    <span id="buttonText">Sign In</span>
                    <span id="buttonLoader" class="button-loader" style="display: none;">
                        <span class="spinner"></span>
                        Signing in...
                    </span>
                </button>

            </form>

            <div class="security-note">
                🔒 Your account is protected by secure authentication.
            </div>

        </div>

    </div>

</div>

<script>
    const password = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');

    togglePassword.addEventListener('click', function () {
        if (password.type === 'password') {
            password.type = 'text';
            this.style.textDecoration = 'line-through';
            this.setAttribute('aria-label', 'Hide password');
        } else {
            password.type = 'password';
            this.style.textDecoration = 'none';
            this.setAttribute('aria-label', 'Show password');
        }
    });

    // Login loading state
    const loginForm = document.querySelector('form');
    const loginButton = document.getElementById('loginButton');
    const buttonText = document.getElementById('buttonText');
    const buttonLoader = document.getElementById('buttonLoader');

    loginForm.addEventListener('submit', function () {
        password.type = 'password';
        loginButton.disabled = true;
        buttonText.style.display = 'none';
        buttonLoader.style.display = 'flex';
    });
</script>

</body>
</html>
