<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>دخول سوبر فايزر</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:grid;place-items:center;padding:20px;background:linear-gradient(140deg,#10213e,#1c477d);font-family:system-ui,-apple-system,"Segoe UI",sans-serif;color:#172033}
        .login-card{width:min(100%,430px);padding:34px;background:#fff;border-radius:18px;box-shadow:0 22px 70px #07162b66}
        .login-mark{display:grid;place-items:center;width:54px;height:54px;margin-bottom:18px;border-radius:15px;background:#e8f0fb;color:#194778;font-size:24px}
        h1{margin:0;color:#17365d;font-size:25px}
        .login-subtitle{margin:8px 0 24px;color:#65758b;line-height:1.7}
        label{display:block;margin:14px 0 6px;color:#34445a;font-weight:650;font-size:14px}
        input[type=email],input[type=password]{width:100%;padding:12px;border:1px solid #ccd6e3;border-radius:9px;font:inherit}
        input:focus{outline:3px solid #d7e8fb;border-color:#3978b7}
        .remember{display:flex;align-items:center;gap:8px;margin:16px 0;color:#596a80;font-size:14px}
        button{width:100%;padding:12px;border:0;border-radius:9px;background:#205b97;color:white;font:inherit;font-weight:750;cursor:pointer}
        button:hover{background:#174775}
        .error{margin:8px 0;color:#b42318;font-size:13px}
        .secure-note{margin-top:20px;padding-top:16px;border-top:1px solid #e8edf4;color:#7b8797;font-size:12px;text-align:center}
    </style>
</head>
<body>
<main class="login-card">
    <div class="login-mark" aria-hidden="true">◆</div>
    <h1>دخول مدير النظام</h1>
    <p class="login-subtitle">لوحة مستقلة لإدارة الشركات ومستخدمي النظام.</p>
    <form method="post" action="{{ route('superadmin.login.handle') }}" autocomplete="on">
        @csrf
        <label for="email">البريد الإلكتروني</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" autofocus>
        @error('email')<p class="error" role="alert">{{ $message }}</p>@enderror
        @error('password')<p class="error" role="alert">{{ $message }}</p>@enderror

        <label for="password">كلمة المرور</label>
        <input id="password" name="password" type="password" required autocomplete="current-password">

        <label class="remember"><input type="checkbox" name="remember" value="1"> تذكرني على هذا الجهاز</label>
        <button type="submit">تسجيل الدخول إلى سوبر فايزر</button>
    </form>
    <div class="secure-note">دخول مخصص لحسابات سوبر فايزر فقط</div>
</main>
</body>
</html>
