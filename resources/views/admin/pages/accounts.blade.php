@extends('admin.layouts.app')
@section('title')دليل الحسابات@endsection
@section('page_title')دليل الحسابات@endsection
@section('content')

<div class="panel">
  <div class="panel-head">
    <h3>📚 الحسابات</h3>
    <input id="search" type="text" class="search-inline" placeholder="🔍 بحث برقم أو اسم...">
    <div class="toolbar">
      @if(in_array($user->role, ['admin', 'accountant', 'account_user'], true))
      <button class="btn btn-teal btn-sm" onclick="openAccModal()">➕ حساب جديد</button>
      @endif
      @if(in_array($user->role, ['admin', 'accountant', 'account_user'], true))
      <button class="btn btn-dark btn-sm" onclick="openModal('importModal')">📥 استيراد</button>
      @endif
      <a class="btn btn-dark btn-sm" href="/templates/accounts.xlsx">📄 القالب</a>
      <a class="btn btn-gold btn-sm" href="/accounts/export.xlsx">📊 تصدير Excel</a>
      @if($user->role === 'admin')
      <button class="btn btn-red btn-sm" onclick="wipeAll()">🗑️ مسح الشجرة كاملة</button>
      @endif
    </div>
  </div>
  <div class="tbl-wrap">
    <table class="tbl" id="acc-table">
      <thead>
        <tr>
          <th class="num">رقم الحساب</th><th>اسم الحساب</th><th>النوع</th>
          <th class="num">الرصيد الافتتاحي</th><th class="num">الرصيد الحالي</th><th class="num">الميزانية التقديرية</th><th class="num">إجراءات</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<!-- مودال إضافة / تعديل -->
<div class="modal-overlay" id="accModal">
  <div class="modal">
    <div class="modal-head"><h3 id="acc-modal-title">حساب جديد</h3>
      <button class="modal-close" data-close-modal>×</button></div>
    <div class="modal-body">
      <input type="hidden" id="f-id">
      <div class="field"><label>رقم الحساب *</label><input id="f-no" type="text" placeholder="مثال: 1101"></div>
      <div class="field"><label>اسم الحساب *</label><input id="f-name" type="text" placeholder="مثال: الصندوق"></div>
      <div class="field"><label>نوع الحساب</label>
        <select id="f-type">
          <option value="">— بدون —</option>
          @foreach(['أصول','خصوم','حقوق ملكية','إيرادات','مصروفات','أخرى'] as $t)
          <option value="{{ $t }}">{{ $t }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label>الرصيد الافتتاحي</label>
        <input id="f-open" type="number" step="0.01" value="0">
        <small id="opening-balance-hint" class="hint">الموجب مدين والسالب دائن. لحساب الإيراد، الرصيد الافتتاحي الدائن يُدخل بالسالب.</small>
      </div>
      <div class="field"><label>الميزانية التقديرية (سنوي)</label><input id="f-budget" type="number" step="0.01" value="0"></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-gold" onclick="saveAccount()">حفظ</button>
      <button class="btn btn-dark" data-close-modal>إلغاء</button>
    </div>
  </div>
</div>

<!-- مودال الاستيراد -->
<div class="modal-overlay" id="importModal">
  <div class="modal">
    <div class="modal-head"><h3>استيراد دليل حسابات من إكسيل</h3>
      <button class="modal-close" data-close-modal>×</button></div>
    <div class="modal-body">
      <div class="alert warn" style="font-size:12.5px">
        املأ قالب «دليل حسابات.xlsx» (اسم الحساب + رقم الحساب) ثم ارفعه هنا.
        الحساب الموجود مسبقًا بنفس الرقم سيتم تحديثه تلقائيًا.
      </div>
      <div class="field"><label>اختر الملف (.xlsx)</label><input type="file" id="imp-file" accept=".xlsx"></div>
      <div id="imp-result"></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-gold" onclick="doImport()">رفع واستيراد</button>
      <button class="btn btn-dark" data-close-modal>إغلاق</button>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
let ACCOUNTS = [];

async function load() {
  try {
    const d = await api('/api/accounts');
    ACCOUNTS = d.accounts;
    render(ACCOUNTS);
  } catch (e) { toast(e.message, 'err'); }
}

function render(list) {
  const tb = $('#acc-table tbody');
  if (!list.length) {
    tb.innerHTML = '<tr><td colspan="7" class="empty-state"><div class="big">📚</div>لا توجد حسابات — أضف أول حساب أو استورد من إكسيل</td></tr>';
    return;
  }
  tb.innerHTML = list.map(a => `
    <tr>
      <td class="num">${esc(a.acc_no)}</td>
      <td>${esc(a.name)} ${a.is_system ? '<span class="badge gray">حساب أساسي مشترك</span>' : ''}</td>
      <td>${a.type ? `<span class="badge region">${esc(a.type)}</span>` : '<span class="badge gray">—</span>'}</td>
      <td class="num">${fmt(a.opening_balance)}</td>
      <td class="num">${fmt(Math.abs(Number(a.current_balance || 0)))} <span class="hint">${accountBalanceSide(a.current_balance)}${a.current_balance_includes_children ? ' · يشمل الفروع' : ''}</span></td>
      <td class="num ${a.budget > 0 ? 'budget-cell' : ''}">${a.budget > 0 ? fmt(a.budget) : '<span class="badge gray">—</span>'}</td>
      <td class="num">
        @if(in_array($user->role, ['admin', 'accountant', 'account_user'], true))
        <button class="btn btn-sm btn-dark act-btn" title="تعديل" onclick='editAcc(${JSON.stringify({id:a.id, acc_no:a.acc_no, name:a.name, type:a.type, opening_balance:a.opening_balance, budget:a.budget, is_system:a.is_system})})'>✏️</button>
        ${a.is_system ? '' : `<button class="btn btn-sm btn-red act-btn" title="حذف" onclick="delAcc(${a.id}, '${esc(a.acc_no)}')">🗑️</button>`}
        @endif
      </td>
    </tr>`).join('');
}

function accountBalanceSide(balance) {
  const amount = Number(balance || 0);
  if (Math.abs(amount) < 0.005) return '—';
  return amount > 0 ? 'مدين' : 'دائن';
}

$('#search').addEventListener('input', e => {
  const q = e.target.value.trim();
  render(!q ? ACCOUNTS : ACCOUNTS.filter(a => a.acc_no.includes(q) || a.name.includes(q)));
});

function openAccModal() {
  $('#acc-modal-title').textContent = 'حساب جديد';
  $('#f-id').value = ''; $('#f-no').value = ''; $('#f-name').value = '';
  $('#f-type').value = ''; $('#f-open').value = '0'; $('#f-budget').value = '0';
  $('#f-no').disabled = false;
  $('#f-name').disabled = false;
  $('#f-type').disabled = false;
  updateOpeningBalanceHint();
  openModal('accModal');
}

function editAcc(a) {
  $('#acc-modal-title').textContent = 'تعديل حساب';
  $('#f-id').value = a.id; $('#f-no').value = a.acc_no;
  $('#f-name').value = a.name; $('#f-type').value = a.type || '';
  $('#f-open').value = a.opening_balance; $('#f-budget').value = a.budget || 0;
  $('#f-no').disabled = Boolean(a.is_system);
  $('#f-name').disabled = Boolean(a.is_system);
  $('#f-type').disabled = Boolean(a.is_system);
  $('#acc-modal-title').textContent = a.is_system
    ? 'تعديل تفاصيل الشركة للحساب الأساسي'
    : 'تعديل حساب';
  updateOpeningBalanceHint();
  openModal('accModal');
}

function updateOpeningBalanceHint() {
  const type = $('#f-type').value;
  const normalSide = ['خصوم', 'حقوق ملكية', 'إيرادات'].includes(type) ? 'دائن' : 'مدين';
  $('#opening-balance-hint').textContent = `الرصيد الطبيعي لهذا الحساب ${normalSide}. الموجب مدين والسالب دائن؛ لحساب الإيراد أدخل الرصيد الدائن بالسالب.`;
}

async function saveAccount() {
  const body = {
    acc_no: $('#f-no').value.trim(),
    name: $('#f-name').value.trim(),
    type: $('#f-type').value,
    opening_balance: parseFloat($('#f-open').value || 0),
    budget: parseFloat($('#f-budget').value || 0),
  };
  if (!body.acc_no || !body.name) return toast('رقم الحساب واسم الحساب مطلوبان', 'err');
  try {
    const id = $('#f-id').value;
    if (id) await api('/api/accounts/' + id, { method: 'PUT', body });
    else await api('/api/accounts', { method: 'POST', body });
    closeModal('accModal');
    toast(id ? 'تم تعديل الحساب' : 'تمت إضافة الحساب');
    load();
  } catch (e) { toast(e.message, 'err'); }
}

async function delAcc(id, no) {
  if (!await confirmDlg(`حذف الحساب ${no}؟ لا يمكن الحذف إذا كان له حركات.`)) return;
  try { await api('/api/accounts/' + id, { method: 'DELETE' }); toast('تم حذف الحساب'); load(); }
  catch (e) { toast(e.message, 'err'); }
}

async function doImport() {
  const f = $('#imp-file').files[0];
  if (!f) return toast('اختر ملفًا أولًا', 'err');
  const fd = new FormData();
  fd.append('file', f);
  try {
    const res = await fetch('/api/accounts/import', { method: 'POST', body: fd, headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
    const d = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(d.error || Object.values(d.errors || {}).flat()[0] || d.message || 'فشل الاستيراد');
    $('#imp-result').innerHTML =
      `<div class="alert ok">✔ تم استيراد <b>${d.added}</b> حساب جديد وتحديث <b>${d.updated}</b> حساب موجود</div>` +
      (d.errors.length ? `<div class="alert warn">${d.errors.map(esc).join('<br>')}</div>` : '');
    toast('تم الاستيراد بنجاح');
    load();
  } catch (e) {
    $('#imp-result').innerHTML = `<div class="alert err">${esc(e.message)}</div>`;
  }
}

$('#f-type').addEventListener('change', updateOpeningBalanceHint);

async function wipeAll() {
  if (!await confirmDlg('تحذير نهائي ⚠️ سيتم حذف الحسابات المخصصة وكل القيود، مع الحفاظ على الحسابات الأساسية المشتركة. متأكد؟')) return;
  if (!await confirmDlg('تأكيد أخير: نفّذ المسح الآن؟ لا يمكن التراجع عن القيود إلا بالنسخ الاحتياطي')) return;
  try {
    const d = await api('/api/accounts/wipe', { method: 'POST' });
    toast(`تم مسح ${d.deleted_accounts} حساب مخصص و${d.deleted_entries} قيد مع الحفاظ على الحسابات الأساسية`);
    load();
  } catch (e) { toast(e.message, 'err'); }
}

async function loadAccountPage() {
  const params = new URLSearchParams(location.search);
  const q = params.get('q') || '';
  const shouldEdit = params.get('edit') === '1';
  $('#search').value = q;

  try {
    const d = await api('/api/accounts');
    ACCOUNTS = d.accounts;
    const filtered = q ? ACCOUNTS.filter(a => a.acc_no.includes(q) || a.name.includes(q)) : ACCOUNTS;
    render(filtered);

    if (shouldEdit) {
      const account = ACCOUNTS.find(a => String(a.acc_no) === q);
      if (!account) {
        toast('الحساب المطلوب غير موجود في دليل الحسابات', 'err');
        return;
      }
      editAcc(account);
    }
  } catch (e) { toast(e.message, 'err'); }
}

loadAccountPage();
</script>
@endsection
