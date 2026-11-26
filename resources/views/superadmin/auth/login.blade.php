<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>دخول مدير النظام | MarinaX</title>
    <link rel="stylesheet" href="{{ asset('css/login-pages.css') }}">
</head>
<body class="auth-page">
    <div class="auth-language-switcher">@include('shared.language-switcher')</div>

    <main class="login-wrapper">
        <aside class="login-info">
            <a class="brand" href="{{ url('/') }}" aria-label="MarinaX">
                <span class="brand-icon" aria-hidden="true">M</span>
                <span class="brand-name">Marina<span>X</span></span>
            </a>

            <div class="info-content">
                <span class="badge">MARINAX PLATFORM</span>
                <h1>إدارة مركزية<br>بكل أمان</h1>
                <p>مساحة مخصصة لإدارة الشركات والمستخدمين وإعدادات المنصة من مكان واحد.</p>
            </div>

            <div class="features">
                <div class="feature"><span class="feature-icon" aria-hidden="true">✓</span>إدارة الشركات المستأجرة</div>
                <div class="feature"><span class="feature-icon" aria-hidden="true">✓</span>تنظيم المستخدمين والصلاحيات</div>
                <div class="feature"><span class="feature-icon" aria-hidden="true">✓</span>وصول محمي ومخصص</div>
            </div>
        </aside>

        <section class="login-form-container">
            <div class="login-form">
                <div class="form-header">
                    <h2>دخول مدير النظام</h2>
                    <p>سجّل الدخول إلى لوحة إدارة المنصة.</p>
                </div>

                @if ($errors->any())
                    <div class="general-errors" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="post" action="{{ route('superadmin.login.handle') }}" autocomplete="on">
                    @csrf

                    <div class="form-group">
                        <label for="email">البريد الإلكتروني</label>
                        <div class="input-wrapper">
                            <span class="input-icon" aria-hidden="true">✉</span>
                            <input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="name@example.com" required autocomplete="username" autofocus>
                        </div>
                        @error('email')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group">
                        <label for="password">كلمة المرور</label>
                        <div class="input-wrapper">
                            <span class="input-icon" aria-hidden="true">🔒</span>
                            <input class="form-input" id="password" name="password" type="password" placeholder="أدخل كلمة المرور" required autocomplete="current-password">
                            <button class="password-toggle" id="togglePassword" type="button" aria-label="إظهار كلمة المرور" aria-controls="password">👁</button>
                        </div>
                        @error('password')<p class="error-message" role="alert">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember" value="1">
                            تذكرني على هذا الجهاز
                        </label>
                    </div>

                    <button class="login-button" id="loginButton" type="submit">
                        <span id="buttonText">تسجيل الدخول</span>
                        <span class="button-loader" id="buttonLoader" style="display:none">
                            <span class="spinner" aria-hidden="true"></span>
                            جارٍ تسجيل الدخول...
                        </span>
                    </button>
                </form>

                <div class="security-note">دخول مخصص لحسابات مدير النظام فقط</div>
            </div>
        </section>
    </main>
    <script>
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('togglePassword');
        const loginForm = document.querySelector('.login-form form');
        const loginButton = document.getElementById('loginButton');
        const buttonText = document.getElementById('buttonText');
        const buttonLoader = document.getElementById('buttonLoader');

        passwordToggle.addEventListener('click', () => {
            const isVisible = passwordInput.type === 'password';
            passwordInput.type = isVisible ? 'text' : 'password';
            passwordToggle.setAttribute('aria-label', isVisible ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
        });

        loginForm.addEventListener('submit', () => {
            passwordInput.type = 'password';
            loginButton.disabled = true;
            buttonText.style.display = 'none';
            buttonLoader.style.display = 'flex';
        });
    </script>
</body>
</html>
