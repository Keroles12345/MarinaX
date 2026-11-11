<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>تسجيل الدخول — النظام المحاسبي</title>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
  <div class="login-wrap">
    <div class="login-card">
      <div class="logo-circle">ح</div>
      <h1>النظام المحاسبي المتكامل</h1>
      <p class="sub">قيود اليومية • الأستاذ العام • ميزان المراجعة</p>
      @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
      <form method="post" action="{{ route('login') }}" autocomplete="on">
        @csrf
        <div class="field">
          <label for="username">اسم المستخدم</label>
          <input id="username" type="text" name="username" required autofocus autocomplete="username" placeholder="admin" value="{{ old('username') }}">
        </div>
        <div class="field">
          <label for="password">كلمة المرور</label>
          <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
        </div>
        <button type="submit" class="btn btn-gold btn-block">دخــول</button>
      </form>
      <p style="margin-top:14px;text-align:center"><a href="/forgot" style="color:var(--gold)">نسيت كلمة المرور؟</a></p>
      <p style="margin-top:24px;color:var(--muted);font-size:12px;line-height:1.8">© جميع الحقوق محفوظة — <b style="color:var(--gold)">محاسب / أحمد عبدالله</b></p>
    </div>
  </div>
</body>
</html>
