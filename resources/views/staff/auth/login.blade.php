
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login</title>

</head>

<body>

<div class="login-wrapper">

    <!-- Left Side -->
    <div class="login-info">

        <div class="brand">
            <div class="brand-icon">
                S
            </div>

            <div class="brand-name">
                 Portal
            </div>
        </div>

        <div class="info-content">

            <span class="badge">
                MANAGEMENT SYSTEM
            </span>

            <h1>
                Welcome<br>
                Back.
            </h1>

            <p>
                Access your workspace, manage your tasks,
                and stay connected with your team from one secure place.
            </p>

        </div>

        <div class="features">

            <div class="feature">
                <div class="feature-icon">✓</div>
                Secure dashboardaccess
            </div>

            <div class="feature">
                <div class="feature-icon">✓</div>
                Department-based workspace
            </div>

            <div class="feature">
                <div class="feature-icon">✓</div>
                Centralized management
            </div>

        </div>

    </div>
<style>


        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(circle at top left, #1e3a8a 0%, transparent 35%),
                radial-gradient(circle at bottom right, #0f766e 0%, transparent 35%),
                #0f172a;
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 1050px;
            min-height: 620px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: rgba(255, 255, 255, 0.96);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.35);
        }

        /* Left Side */

        .login-info {
            position: relative;
            padding: 60px;
            color: white;
            background: linear-gradient(145deg, #1d4ed8, #172554);
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
        }

        .info-content {
            position: relative;
            z-index: 1;
        }

        .info-content .badge {
            display: inline-block;
            padding: 7px 13px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.15);
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
            letter-spacing: 0.5px;
        }

        .info-content h1 {
            font-size: 42px;
            line-height: 1.15;
            margin-bottom: 18px;
        }

        .info-content p {
            color: rgba(255, 255, 255, 0.75);
            font-size: 15px;
            line-height: 1.8;
            max-width: 400px;
        }
.password-toggle {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);

    border: none;
    background: transparent;
    cursor: pointer;

    font-size: 21px;
    color: #9ca3af;
    padding: 5px;
}

.password-toggle:hover {
    color: #2563eb;
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
            color: rgba(255, 255, 255, 0.85);
            font-size: 21px;
        }

        .feature-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Right Side */

        .login-form-container {
            padding: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
        }

        .login-form {
            width: 100%;
            max-width: 390px;
        }

        .form-header {
            margin-bottom: 35px;
        }

        .form-header h2 {
            font-size: 30px;
            color: #111827;
            margin-bottom: 10px;
        }

        .form-header p {
            color: #6b7280;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-size: 13px;
            font-weight: 600;
        }

        .input-wrapper {
            position: relative;
        }

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
            padding: 0 15px 0 45px;
            font-size: 14px;
            color: #111827;
            outline: none;
            background: #f9fafb;
            transition: all 0.2s ease;
        }

        .form-input::placeholder {
            color: #9ca3af;
        }

        .form-input:focus {
            background: white;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.10);
        }

        .error-message {
            margin-top: 7px;
            color: #dc2626;
            font-size: 12px;
        }

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
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
        }

        .login-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 25px rgba(37, 99, 235, 0.35);
        }

        .login-button:active {
            transform: translateY(0);
        }

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
            .login-wrapper {
                grid-template-columns: 1fr;
                max-width: 480px;
            }
.button-loader {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.spinner {
    width: 18px;
    height: 18px;
    border: 3px solid rgba(255, 255, 255, 0.4);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.login-button:disabled {
    cursor: not-allowed;
    opacity: 0.8;
}
            .login-info {
                display: none;
            }

            .login-form-container {
                padding: 45px 30px;
            }
        }

        @media (max-width: 400px) {
            .login-form-container {
                padding: 35px 20px;
            }

            .form-header h2 {
                font-size: 26px;
            }
        }


</style>

    <!-- Right Side -->
    <div class="login-form-container">

        <div class="login-form">

            <div class="form-header">
                <h2>Login</h2>

                <p>
                    Enter your credentials to access your dashboard.
                </p>
            </div>


            @if ($errors->any())
                <div class="general-errors">
                    {{ $errors->first() }}
                </div>
            @endif


            <form action="{{ route('staff.login.handle') }}" method="POST" autocomplete="on">

                @csrf

                <!-- Email -->
                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">
                            ✉
                        </span>

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
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


               <div class="form-group">
    <label for="password">
        Password
    </label>

    <div class="input-wrapper">

        <span class="input-icon">
            🔒
        </span>

        <input
            id="password"
            type="password"
            name="password"
            class="form-input"
            placeholder="Enter your password"
            autocomplete="current-password"
            required
        >

        <button
            type="button"
            id="togglePassword"
            class="password-toggle"
            aria-label="Show password"
        >
            👁
        </button>

    </div>

    @error('password')
        <div class="error-message">
            {{ $message }}
        </div>
    @enderror
</div>

<div class="form-group">
    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
        <input type="hidden" name="remember" value="0">
        <input type="checkbox" name="remember" value="1" {{ old('remember', true) ? 'checked' : '' }}>
        Remember me
    </label>
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


    // =========================
    // Login Loading
    // =========================

    const loginForm = document.querySelector('form');
    const loginButton = document.getElementById('loginButton');
    const buttonText = document.getElementById('buttonText');
    const buttonLoader = document.getElementById('buttonLoader');

    loginForm.addEventListener('submit', function () {
        password.type = 'password';

        // Prevent multiple clicks
        loginButton.disabled = true;

        // Hide Sign In
        buttonText.style.display = 'none';

        // Show loading
        buttonLoader.style.display = 'flex';

    });
</script>

</body>
</html>
