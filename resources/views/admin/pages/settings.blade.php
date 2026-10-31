@extends('admin.layouts.app')
@section('title')الإعدادات@endsection
@section('page_title')الإعدادات@endsection
@section('content')

<div class="grid-2">
  @if($user->role === 'admin')
  <div class="panel">
    <div class="panel-head"><h3>🏢 إعدادات عامة</h3></div>
    <div class="panel-body">
      <div class="field"><label>اسم الشركة (يظهر في رأس التقارير المصدَّرة)</label>
        <input id="s-company" type="text" value="{{ $companyName }}"></div>
      <div class="field"><label>بداية فترة التقارير</label>
        <input id="s-from" type="date" value="{{ $periodFrom }}"></div>
      <div class="field"><label>نهاية فترة التقارير</label>
        <input id="s-to" type="date" value="{{ $periodTo }}"></div>
      <button class="btn btn-gold" onclick="saveSettings()">💾 حفظ الإعدادات</button>
    </div>
  </div>
  @endif

  @if($user->role === 'admin')
  <div class="panel">
    <div class="panel-head"><h3>✍️ التواقيع في التقارير المصدَّرة</h3>
      <button class="btn btn-teal btn-sm" onclick="addSigRow()">➕ إضافة توقيع</button>
    </div>
    <div class="panel-body">
      <div class="alert ok" style="font-size:12.5px">
        أول توقيع «المحاسب» بياخد اسم المستخدم اللي عمل التصدير تلقائيًا.
        باقي التواقيع (المسمى + الاسم) تقدر تعدّلها وتضيفها وتحذفها من هنا — بتظهر في كل التقارير.
      </div>
      <div class="sig-list" id="sig-list"></div>
      <button class="btn btn-gold" style="margin-top:6px" onclick="saveSettings()">💾 حفظ الإعدادات</button>
    </div>
  </div>
  @endif

  @if($user->role === 'admin')
  <div class="panel">
    <div class="panel-head"><h3>🗄️ النسخ الاحتياطية</h3>
      <button class="btn btn-gold btn-sm" onclick="doBackup()">⬇️ نسخة احتياطية الآن</button>
      <button class="btn btn-teal btn-sm" onclick="document.getElementById('restore-file').click()">⬆️ استعادة من ملف</button>
      <input type="file" id="restore-file" accept=".json" style="display:none" onchange="restoreFromFile(this)">
    </div>
    <div class="panel-body">
      <div class="alert ok" style="font-size:12.5px">
        ✔ نسخة أوتوماتيك بتتعمل كل شهر جديد من تلقاء نفسها، وبنحتفظ بآخر 24 نسخة.
        الملفات محفوظة على جهاز السيرفر داخل مجلد <b>storage/app/private/backups</b>
        <br>🔄 زر «استعادة» يسوي نسخة أمان تلقائية للوضع الحالي أولاً، ثم يصدّر
        بيانات النسخة المختارة محل قاعدة البيانات الجارية.
        <br>⬆️ زر «استعادة من ملف» يستعيد نسخة النظام بصيغة JSON.
      </div>
      <div class="tbl-wrap">
        <table class="tbl">
          <thead><tr><th class="num">اسم النسخة</th><th class="num">التاريخ والوقت</th><th class="num">الحجم</th><th class="num">تنزيل</th></tr></thead>
          <tbody id="backups-body"><tr><td colspan="4" class="empty-state">جارٍ التحميل...</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>
  @endif

  <div class="panel">
    <div class="panel-head"><h3>🔑 تغيير كلمة المرور الخاصة بي</h3></div>
    <div class="panel-body">
      <div class="field"><label>كلمة المرور الحالية</label><input id="p-old" type="password"></div>
      <div class="field"><label>كلمة المرور الجديدة (8 أحرف على الأقل، حروف وأرقام)</label><input id="p-new" type="password" autocomplete="new-password"></div>
      <button class="btn btn-teal" onclick="changePw()">تغيير كلمة المرور</button>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h3>ℹ️ عن السيستم</h3></div>
    <div class="panel-body hint" style="font-size:13px;line-height:2.1">
      <p>📚 دليل حسابات + استيراد/تصدير Excel بصيغة xlsx</p>
      <p>📒 دفتر اليومية برقم حركة وترحيل القيود</p>
      <p>📖 الأستاذ العام بفلترة رقم الحساب والمنطقة</p>
      <p>⚖️ ميزان المراجعة بالأرصدة الافتتاحية</p>
      <p>👥 يعمل عليه عدة مستخدمين في نفس الوقت عبر الشبكة</p>
      <p style="color:var(--gold)">© جميع الحقوق محفوظة — محاسب / أحمد عبدالله</p>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
@if($user->role === 'admin')
function fmtSize(b) {
  return b > 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';
}

async function loadBackups() {
  try {
    const d = await api('/api/backups');
    const tb = $('#backups-body');
    if (!d.backups.length) {
      tb.innerHTML = '<tr><td colspan="4" class="empty-state">لا توجد نسخ بعد</td></tr>';
      return;
    }
    tb.innerHTML = d.backups.map(b => `
      <tr>
        <td class="num" style="direction:ltr">${esc(b.name)}</td>
        <td class="num">${esc(b.modified)}</td>
        <td class="num">${fmtSize(b.size)}</td>
        <td class="num"><a class="btn btn-sm btn-teal" href="/api/backups/${encodeURIComponent(b.name)}">⬇️ تنزيل</a>
        <button class="btn btn-sm btn-gold" onclick="restoreBackup('${encodeURIComponent(b.name)}')">🔄 استعادة</button></td>
      </tr>`).join('');
  } catch (e) { toast(e.message, 'err'); }
}

async function doBackup() {
  if (!await confirmDlg('إنشاء نسخة احتياطية جديدة الآن؟')) return;
  try {
    const d = await api('/api/backup', { method: 'POST' });
    toast('تم إنشاء النسخة بنجاح ✔');
    location.href = '/api/backups/' + encodeURIComponent(d.name);
    loadBackups();
  } catch (e) { toast(e.message, 'err'); }
}

async function restoreBackup(name) {
  name = decodeURIComponent(name);
  if (!await confirmDlg('استعادة النسخة «' + name + '»؟\nستُسوى نسخة أمان تلقائية للوضع الحالي قبل الاستعادة، ثم تُسيقاد بيانات النسخة المختارة. متابعة؟')) return;
  try {
    const d = await api('/api/restore/' + encodeURIComponent(name), { method: 'POST' });
    toast(d.message || 'تمت الاستعادة ✔');
    setTimeout(() => location.reload(), 1200);
  } catch (e) { toast(e.message, 'err'); }
}

async function restoreFromFile(input) {
  const file = input.files[0];
  input.value = '';
  if (!file) return;
  if (!await confirmDlg('استعادة النسخة من الملف «' + file.name + '»؟\nستُسوى نسخة أمان تلقائية للوضع الحالي قبل الاستعادة، ثم تُسيقاد بيانات الملف المختار. متابعة؟')) return;
  const fd = new FormData();
  fd.append('file', file);
  try {
    const res = await fetch('/api/restore-file', { method: 'POST', body: fd, headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.error || Object.values(data.errors || {}).flat()[0] || data.message || 'حدث خطأ غير متوقع');
    toast(data.message || 'تمت الاستعادة ✔');
    setTimeout(() => location.reload(), 1800);
  } catch (e) { toast(e.message, 'err'); }
}

loadBackups();
@endif

@if($user->role === 'admin')
const SIGS = @json($signatures);
function renderSigs() {
  const c = $('#sig-list');
  if (!c) return;
  c.innerHTML = SIGS.map((s, i) => `
    <div class="sig-row" data-i="${i}">
      <input class="sig-title" placeholder="المسمى الوظيفي (مثال: مدير إدارة الميزانية)" value="${esc(s.title || '')}">
      <input class="sig-name" placeholder="الاسم (مثال: محاسب / احمد عبدالله)" value="${esc(s.name || '')}">
      <button class="btn btn-sm btn-red" title="حذف التوقيع" onclick="removeSigRow(${i})">🗑️</button>
    </div>`).join('');
  if (!SIGS.length)
    c.innerHTML = '<p class="empty-state">مفيش تواقيع إضافية — أول توقيع (المحاسب) ثابت تلقائيًا</p>';
}
function addSigRow() { SIGS.push({ title: '', name: '' }); renderSigs(); }
function removeSigRow(i) { SIGS.splice(i, 1); renderSigs(); }
renderSigs();

async function saveSettings() {
  try {
    const sigs = SIGS.map((s, i) => {
      const row = document.querySelector(`.sig-row[data-i="${i}"]`);
      return {
        title: row ? row.querySelector('.sig-title').value.trim() : s.title,
        name: row ? row.querySelector('.sig-name').value.trim() : s.name,
      };
    }).filter(s => s.title && s.name);
    await api('/api/settings', { method: 'POST', body: {
      company_name: $('#s-company').value.trim(),
      period_from: $('#s-from').value,
      period_to: $('#s-to').value,
      signatures: sigs,
    }});
    toast('تم حفظ الإعدادات');
  } catch (e) { toast(e.message, 'err'); }
}
@else
async function saveSettings() {
  try {
    await api('/api/settings', { method: 'POST', body: {
      company_name: $('#s-company').value.trim(),
      period_from: $('#s-from').value,
      period_to: $('#s-to').value,
    }});
    toast('تم حفظ الإعدادات');
  } catch (e) { toast(e.message, 'err'); }
}
@endif

async function changePw() {
  try {
    if (!$('#p-old').value || !$('#p-new').value) return toast('املأ الخانتين', 'err');
    if ($('#p-new').value.length < 8) return toast('كلمة المرور الجديدة 8 أحرف على الأقل', 'err');
    await api('/api/me/password', { method: 'POST', body: { old: $('#p-old').value, new: $('#p-new').value } });
    toast('تم تغيير كلمة المرور بنجاح');
    $('#p-old').value = ''; $('#p-new').value = '';
  } catch (e) { toast(e.message, 'err'); }
}
</script>
@endsection
