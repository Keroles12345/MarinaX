<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>استعادة كلمة المرور — النظام المحاسبي</title>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
  <div class="login-wrap">
    <div class="login-card">
      <div class="logo-circle">ح</div>
      <h1>استعادة كلمة المرور</h1>
      <div class="sub">{{ $companyName ?? 'النظام المحاسبي المتكامل' }}</div>
      @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
      @if(!empty($ok))<div class="alert ok">{{ $ok }}</div>@endif
      @if($step === 'done')
        <div style="text-align:center"><a class="btn btn-gold" href="/login">تسجيل الدخول</a></div>
      @elseif($step === 'answer')
        <form method="post" autocomplete="on">
          @csrf
          <input type="hidden" name="act" value="setup">
          <input type="hidden" name="username" value="{{ $username }}">
          <div class="field"><label>سؤال الأمان</label><div class="hint" style="font-size:15px;padding:8px 10px;border:1px solid var(--line);border-radius:6px;color:var(--text)">{{ $question }}</div></div>
          <div class="field"><label>إجابة سؤال الأمان</label><input type="text" name="answer" required autofocus autocomplete="off"></div>
          <div class="field"><label>كلمة المرور الجديدة (8 أحرف على الأقل، حروف وأرقام)</label><input type="password" name="new_password" required minlength="8" autocomplete="new-password"></div>
          <button class="btn btn-gold btn-block" type="submit">تغيير كلمة المرور</button>
        </form>
      @else
        <form method="post" autocomplete="on">
          @csrf
          <div class="field"><label>اسم المستخدم</label><input type="text" name="username" required autofocus autocomplete="username" value="{{ $username }}"></div>
          <button class="btn btn-gold btn-block" type="submit">متابعة</button>
        </form>
      @endif
      <p style="margin-top:18px;text-align:center"><a href="/login" style="color:var(--gold)">العودة لتسجيل الدخول</a></p>
      <p style="margin-top:20px;color:var(--muted);font-size:12px;line-height:1.8">© جميع الحقوق محفوظة — <b style="color:var(--gold)">محاسب / أحمد عبدالله</b></p>
    </div>
  </div>
</body>
</html>
