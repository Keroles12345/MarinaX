@extends('admin.layouts.app')
@section('title')المستخدمون@endsection
@section('page_title')إدارة المستخدمين@endsection
@section('content')
<style>
  .users-page{display:grid;gap:18px}
  .users-hero{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:22px 24px;border:1px solid #e6ebf2;border-radius:16px;background:linear-gradient(115deg,#fff 0%,#f8fafc 72%,#fff8e8 100%);box-shadow:0 6px 20px rgba(31,41,55,.045)}
  .users-hero-copy{display:grid;gap:5px}
  .users-eyebrow{color:#94702a;font-size:12px;font-weight:800}
  .users-hero h3{color:#1d2939;font-size:21px;font-weight:800}
  .users-hero p{margin:0;color:#667085;font-size:13.5px}
  .users-create{flex:0 0 auto}
  .users-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
  .users-stat{display:flex;align-items:center;gap:13px;min-height:82px;padding:15px 18px;border:1px solid #e6ebf2;border-radius:13px;background:#fff;box-shadow:0 4px 14px rgba(31,41,55,.035)}
  .users-stat-icon{display:grid;place-items:center;width:42px;height:42px;flex:0 0 42px;border-radius:12px;background:#fff6df;font-size:19px}
  .users-stat:nth-child(2) .users-stat-icon{background:#eaf7f5}
  .users-stat:nth-child(3) .users-stat-icon{background:#eef4ff}
  .users-stat-label{color:#667085;font-size:12px;font-weight:600}
  .users-stat-value{margin-top:2px;color:#1d2939;font-size:22px;font-weight:800;line-height:1.2}
  .users-panel{border-color:#e3e8ef;border-radius:14px}
  .users-panel-head{justify-content:space-between;padding:15px 18px;background:#fff}
  .users-panel-title{display:grid;gap:3px}
  .users-panel-title h3{color:#1d2939;font-size:15px}
  .users-panel-title span{color:#667085;font-size:12px;font-weight:500}
  .users-search{position:relative;width:min(100%,310px)}
  .users-search .sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
  .users-search::before{content:'⌕';position:absolute;inset-inline-start:12px;top:50%;transform:translateY(-56%);color:#98a2b3;font-size:21px;pointer-events:none}
  .users-search input{min-height:40px;padding:8px 38px 8px 12px;border-color:#dce3ec;border-radius:10px;font-size:13px}
  .users-table-wrap{max-height:calc(100vh - 360px);min-height:190px}
  .users-table{min-width:1060px}
  .users-table th{padding-top:13px;padding-bottom:13px;color:#667085;font-size:12px}
  .users-table td{padding-top:13px;padding-bottom:13px}
  .users-person{display:flex;align-items:center;gap:10px;min-width:150px}
  .users-avatar{display:grid;place-items:center;width:36px;height:36px;flex:0 0 36px;border-radius:11px;background:#eef4ff;color:#315b9a;font-size:14px;font-weight:800}
  .users-person-info{display:grid;gap:2px;min-width:0}
  .users-person-name{overflow:hidden;color:#1d2939;font-weight:700;text-overflow:ellipsis;white-space:nowrap}
  .users-person-username{color:#98a2b3;font-size:11.5px;direction:ltr;text-align:start}
  .users-email{color:#475467;direction:ltr;text-align:right}
  .users-regions{max-width:230px;color:#475467;line-height:1.6}
  .users-actions{display:flex;flex-wrap:wrap;justify-content:flex-start;gap:5px;min-width:210px}
  .users-actions .btn{min-height:30px}
  .users-actions .btn-dark{background:#f2f4f7;border-color:#e4e7ec;color:#475467}
  .users-actions .btn-dark:hover{background:#e9edf3}
  .users-actions .btn-teal{background:#eaf7f5;border:1px solid #cce9e4;color:#147d73}
  .users-actions .btn-red{background:#fef2f2;border:1px solid #fecaca;color:#b42318}
  .users-result-count{padding:10px 18px;border-top:1px solid #edf0f4;color:#667085;font-size:12px}
  .users-note{display:flex;align-items:flex-start;gap:10px;padding:14px 18px;border-top:1px solid #edf0f4;background:#f8fafc;color:#667085;font-size:12.5px;line-height:1.8}
  .users-note-icon{color:#94702a;font-size:16px}
  @media(max-width:760px){.users-page{gap:13px}.users-hero{align-items:flex-start;padding:18px;flex-direction:column}.users-hero h3{font-size:19px}.users-create{width:100%}.users-stats{grid-template-columns:1fr;gap:9px}.users-stat{min-height:68px;padding:11px 14px}.users-panel-head{align-items:stretch;flex-direction:column}.users-search{width:100%}.users-table-wrap{max-height:none}.users-result-count{padding-inline:14px}}
</style>

<div class="users-page">
  <section class="users-hero">
    <div class="users-hero-copy">
      <span class="users-eyebrow">إدارة النظام</span>
      <h3>إدارة حسابات المستخدمين</h3>
      <p>تابع الحسابات والصلاحيات ومناطق عمل الموظفين من مكان واحد.</p>
    </div>
    @systemPermission('users', 'create')
    <button class="btn btn-gold users-create" onclick="openModal('addModal')">＋ مستخدم جديد</button>
    @endsystemPermission
  </section>

  <section class="users-stats" aria-label="ملخص المستخدمين">
    <div class="users-stat">
      <span class="users-stat-icon" aria-hidden="true">👥</span>
      <div><div class="users-stat-label">إجمالي المستخدمين</div><div class="users-stat-value">{{ $users->count() }}</div></div>
    </div>
    <div class="users-stat">
      <span class="users-stat-icon" aria-hidden="true">🧑‍💼</span>
      <div><div class="users-stat-label">الموظفون</div><div class="users-stat-value">{{ $users->where('role', 'staff')->count() }}</div></div>
    </div>
    <div class="users-stat">
      <span class="users-stat-icon" aria-hidden="true">🛡️</span>
      <div><div class="users-stat-label">سؤال أمان مُفعّل</div><div class="users-stat-value">{{ $users->filter(fn ($listedUser) => $listedUser->security_question)->count() }}</div></div>
    </div>
  </section>

  <section class="panel users-panel">
    <div class="panel-head users-panel-head">
      <div class="users-panel-title">
        <h3>قائمة المستخدمين</h3>
        <span id="users-result-count" aria-live="polite">يعرض {{ $users->count() }} من {{ $users->count() }} مستخدم</span>
      </div>
      <label class="users-search">
        <span class="sr-only">ابحث بالاسم أو اسم الدخول أو البريد الإلكتروني</span>
        <input id="users-search" type="search" autocomplete="off" placeholder="ابحث بالاسم أو اسم الدخول أو البريد الإلكتروني">
      </label>
    </div>
    <div class="tbl-wrap users-table-wrap">
      <table class="tbl users-table">
        <thead><tr><th>المستخدم</th><th>البريد الإلكتروني</th><th>الصلاحية</th><th>المناطق</th><th>سؤال الأمان</th><th class="num">تاريخ الإضافة</th><th>إجراءات</th></tr></thead>
      <tbody>
        @forelse($users as $u)
        <tr data-user-row data-search="{{ strtolower($u->name.' '.$u->username.' '.$u->email.' '.$u->role) }}">
          <td>
            <div class="users-person">
              <span class="users-avatar" aria-hidden="true">{{ mb_substr($u->name, 0, 1) }}</span>
              <span class="users-person-info">
                <span class="users-person-name">{{ $u->name }}</span>
                <span class="users-person-username">{{ $u->username }}</span>
              </span>
            </div>
          </td>
          <td class="users-email">{{ $u->email }}</td>
          <td>@if($u->role === 'admin')<span class="badge posted">مدير النظام</span>
              @elseif($u->role === 'accountant')<span class="badge region">محاسب</span>
              @elseif($u->role === 'viewer')<span class="badge gray">مشاهدة فقط</span>
              @elseif($u->role === 'account_user')<span class="badge gray">موظف حسابات</span>
              @elseif($u->role === 'staff')<span class="badge region">موظف</span>
              @elseif($u->role === 'supervisor')<span class="badge region">مشرف</span>
              @else<span class="badge gray">عميل</span>@endif</td>
          <td>
            @if($u->role === 'staff')
              <span class="users-regions">{{ $u->regions->pluck('name')->join('، ') ?: 'غير محددة' }}</span>
              @systemPermission('users', 'update')
              <button class="btn btn-sm btn-dark" data-user-action="regions"
                data-id="{{ $u->id }}" data-username="{{ $u->username }}"
                data-region-ids="{{ $u->regions->pluck('id')->implode(',') }}">تحديد</button>
              @endsystemPermission
            @else
              —
            @endif
          </td>
          <td>@if($u->security_question)<span class="badge posted" title="{{ $u->security_question }}">مُفعّل</span>
              @else<span class="badge draft">بدون سؤال</span>@endif</td>
          <td class="num">{{ $u->created_at?->format('Y-m-d H:i') }}</td>
          <td>
            <div class="users-actions">
            @systemPermission('users', 'update')
            <button class="btn btn-sm btn-dark" data-user-action="edit"
              data-id="{{ $u->id }}" data-name="{{ $u->name }}" data-username="{{ $u->username }}"
              data-email="{{ $u->email }}" data-role="{{ $u->role }}">✏️ تعديل</button>
            <button class="btn btn-sm btn-dark" data-user-action="security"
              data-id="{{ $u->id }}" data-username="{{ $u->username }}"
              data-question="{{ $u->security_question }}">🛡️ سؤال أمان</button>
            <button class="btn btn-sm btn-teal" data-user-action="password"
              data-id="{{ $u->id }}" data-username="{{ $u->username }}">🔑 كلمة مرور</button>
            @endsystemPermission
            @if($u->id !== $user->id)
            @systemPermission('users', 'delete')
            <button class="btn btn-sm btn-red" data-user-action="delete"
              data-id="{{ $u->id }}" data-username="{{ $u->username }}">🗑️</button>
            @endsystemPermission
            @endif
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="7" class="empty-state">لا يوجد مستخدمون لعرضهم</td></tr>
        @endforelse
        <tr id="users-filter-empty" hidden><td colspan="7" class="empty-state">لا توجد نتائج مطابقة لبحثك</td></tr>
      </tbody>
      </table>
    </div>
    <div class="users-note">
      <span class="users-note-icon" aria-hidden="true">ⓘ</span>
      <span>سؤال الأمان يتيح للمستخدم استعادة حسابه عند نسيان كلمة المرور. تغيير كلمة المرور يحدّث دخول النظام المحاسبي ودخول الموظفين معًا.</span>
    </div>
  </section>
</div>

<!-- مودال إضافة مستخدم -->
@systemPermission('users', 'create')
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-head"><h3>مستخدم جديد</h3><button class="modal-close" data-close-modal>×</button></div>
    <div class="modal-body">
      <div class="field"><label>الاسم الكامل *</label><input id="u-name" type="text"></div>
      <div class="field"><label>اسم الدخول *</label><input id="u-user" type="text" placeholder="بالإنجليزية"></div>
      <div class="field"><label>البريد الإلكتروني *</label><input id="u-email" type="email" autocomplete="username"></div>
      <div class="field"><label>كلمة المرور *</label><input id="u-pass" type="password" autocomplete="new-password"></div>
      <div class="field"><label>سؤال الأمان (اختياري — لتفعيل «نسيت كلمة المرور؟»)</label><input id="u-q" type="text" placeholder="مثال: اسم مدرستك الثانوية؟"></div>
      <div class="field"><label>إجابة سؤال الأمان (اختياري)</label><input id="u-ans" type="text"></div>
      <div class="field"><label>الصلاحية</label>
        <select id="u-role">
          <option value="accountant">محاسب (كل الصلاحيات المحاسبية)</option>
          <option value="admin">مدير النظام</option>
          <option value="viewer">مشاهدة فقط</option>
          <option value="account_user">موظف حسابات (عرض الحسابات فقط — دخول الأدمن)</option>
          <option value="staff">موظف</option>
          <option value="supervisor">مشرف</option>
          <option value="customer">عميل</option>
        </select></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-gold" onclick="addUser()">حفظ</button>
      <button class="btn btn-dark" data-close-modal>إلغاء</button>
    </div>
  </div>
</div>
@endsystemPermission

@systemPermission('users', 'update')
<div class="modal-overlay" id="user-regions-modal">
  <div class="modal">
    <div class="modal-head"><h3 id="user-regions-title">مناطق الموظف</h3><button class="modal-close" data-close-modal>×</button></div>
    <div class="modal-body">
      <input type="hidden" id="user-regions-id">
      <p class="hint">سيظهر للموظف فقط ما يرتبط بفنادق المناطق المحددة. بدون مناطق لن تظهر له عمليات.</p>
      <div id="user-regions-options">
        @forelse($regions as $region)
          <label class="field" style="display:flex;align-items:center;gap:.6rem">
            <input class="user-region-choice" type="checkbox" value="{{ $region->id }}">
            {{ $region->name }}
          </label>
        @empty
          <p class="hint">أضف منطقة نشطة أولًا من صفحة إدارة المناطق.</p>
        @endforelse
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-gold" onclick="saveUserRegions()">حفظ المناطق</button>
      <button class="btn btn-dark" data-close-modal>إلغاء</button>
    </div>
  </div>
</div>

<!-- مودال تعديل بيانات المستخدم -->
<div class="modal-overlay" id="editUserModal">
  <div class="modal">
    <div class="modal-head"><h3>تعديل بيانات المستخدم</h3><button class="modal-close" data-close-modal>×</button></div>
    <div class="modal-body">
      <input type="hidden" id="edit-user-id">
      <div class="field"><label>الاسم الكامل *</label><input id="edit-user-name" type="text"></div>
      <div class="field"><label>اسم الدخول *</label><input id="edit-user-username" type="text"></div>
      <div class="field"><label>البريد الإلكتروني *</label><input id="edit-user-email" type="email"></div>
      <div class="field"><label>الصلاحية</label>
        <select id="edit-user-role">
          <option value="accountant">محاسب</option>
          <option value="admin">مدير النظام</option>
          <option value="viewer">مشاهدة فقط</option>
          <option value="account_user">موظف حسابات (عرض الحسابات فقط — دخول الأدمن)</option>
          <option value="staff">موظف</option>
          <option value="supervisor">مشرف</option>
          <option value="customer">عميل</option>
        </select></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-gold" onclick="saveUser()">حفظ التعديلات</button>
      <button class="btn btn-dark" data-close-modal>إلغاء</button>
    </div>
  </div>
</div>

<!-- مودال سؤال الأمان -->
<div class="modal-overlay" id="secModal">
  <div class="modal">
    <div class="modal-head"><h3 id="sec-title">سؤال الأمان</h3><button class="modal-close" data-close-modal>×</button></div>
    <div class="modal-body">
      <input type="hidden" id="sec-id">
      <div class="field"><label>سؤال الأمان</label><input id="sec-q" type="text" placeholder="مثال: اسم مدرستك الثانوية؟"></div>
      <div class="field"><label>إجابة سؤال الأمان</label><input id="sec-ans" type="text"></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-gold" onclick="saveSecurity()">حفظ</button>
      <button class="btn btn-dark" data-close-modal>إلغاء</button>
    </div>
  </div>
</div>

<!-- مودال كلمة المرور -->
<div class="modal-overlay" id="pwModal">
  <div class="modal">
    <div class="modal-head"><h3 id="pw-title">كلمة مرور جديدة</h3><button class="modal-close" data-close-modal>×</button></div>
    <div class="modal-body">
      <input type="hidden" id="pw-id">
      <div class="field"><label>كلمة المرور الجديدة</label><input id="pw-new" type="password" autocomplete="new-password"></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-gold" onclick="savePw()">تغيير</button>
      <button class="btn btn-dark" data-close-modal>إلغاء</button>
    </div>
  </div>
</div>
@endsystemPermission

@endsection

@section('scripts')
<script>
(() => {
  const search = document.getElementById('users-search');
  const rows = [...document.querySelectorAll('[data-user-row]')];
  const emptyRow = document.getElementById('users-filter-empty');
  const resultCount = document.getElementById('users-result-count');
  if (!search || !resultCount) return;

  const total = rows.length;
  search.addEventListener('input', () => {
    const query = search.value.trim().toLocaleLowerCase();
    let visible = 0;
    rows.forEach(row => {
      const matches = row.dataset.search.includes(query);
      row.hidden = !matches;
      if (matches) visible++;
    });
    if (emptyRow) emptyRow.hidden = visible > 0 || total === 0;
    resultCount.textContent = `يعرض ${visible} من ${total} مستخدم`;
  });
})();

document.addEventListener('click', event => {
  const button = event.target.closest('[data-user-action]');
  if (!button) return;
  const { id, username, name, email, role, question } = button.dataset;
  if (button.dataset.userAction === 'edit') {
    $('#edit-user-id').value = id;
    $('#edit-user-name').value = name;
    $('#edit-user-username').value = username;
    $('#edit-user-email').value = email;
    $('#edit-user-role').value = role;
    openModal('editUserModal');
  } else if (button.dataset.userAction === 'security') {
    setSecurity(id, username, question);
  } else if (button.dataset.userAction === 'password') {
    resetPw(id, username);
  } else if (button.dataset.userAction === 'regions') {
    openUserRegions(id, username, button.dataset.regionIds);
  } else if (button.dataset.userAction === 'delete') {
    delUser(id, username);
  }
});

function openUserRegions(id, username, regionIds) {
  const selectedIds = new Set((regionIds || '').split(',').filter(Boolean));
  $('#user-regions-id').value = id;
  $('#user-regions-title').textContent = `مناطق الموظف: ${username}`;
  document.querySelectorAll('.user-region-choice').forEach(input => {
    input.checked = selectedIds.has(input.value);
  });
  openModal('user-regions-modal');
}

async function saveUserRegions() {
  const regionIds = [...document.querySelectorAll('.user-region-choice:checked')].map(input => Number(input.value));
  try {
    await api(`/api/users/${$('#user-regions-id').value}/regions`, { method: 'PUT', body: { region_ids: regionIds } });
    closeModal('user-regions-modal');
    toast('تم حفظ مناطق الموظف');
    setTimeout(() => location.reload(), 500);
  } catch (e) { toast(e.message, 'err'); }
}

async function addUser() {
  try {
    await api('/api/users', { method: 'POST', body: {
      name: $('#u-name').value.trim(),
      username: $('#u-user').value.trim(),
      email: $('#u-email').value.trim(),
      password: $('#u-pass').value,
      role: $('#u-role').value,
      security_question: $('#u-q').value.trim(),
      security_answer: $('#u-ans').value.trim(),
    }});
    closeModal('addModal');
    toast('تمت إضافة المستخدم');
    setTimeout(() => location.reload(), 500);
  } catch (e) { toast(e.message, 'err'); }
}

async function saveUser() {
  const id = $('#edit-user-id').value;
  try {
    await api(`/api/users/${id}`, { method: 'PUT', body: {
      name: $('#edit-user-name').value.trim(),
      username: $('#edit-user-username').value.trim(),
      email: $('#edit-user-email').value.trim(),
      role: $('#edit-user-role').value,
    }});
    closeModal('editUserModal');
    toast('تم تحديث بيانات المستخدم');
    setTimeout(() => location.reload(), 500);
  } catch (e) { toast(e.message, 'err'); }
}

function setSecurity(id, username, question) {
  $('#sec-title').textContent = `سؤال الأمان لـ ${username}`;
  $('#sec-id').value = id;
  $('#sec-q').value = question || '';
  $('#sec-ans').value = '';
  openModal('secModal');
}

async function saveSecurity() {
  const q = $('#sec-q').value.trim();
  const a = $('#sec-ans').value.trim();
  if (!q) return toast('اكتب سؤال الأمان', 'err');
  if (!a) return toast('اكتب إجابة سؤال الأمان', 'err');
  try {
    await api(`/api/users/${$('#sec-id').value}/security`, { method: 'POST', body: { security_question: q, security_answer: a } });
    closeModal('secModal');
    toast('تم حفظ سؤال الأمان');
    setTimeout(() => location.reload(), 500);
  } catch (e) { toast(e.message, 'err'); }
}

function resetPw(id, username) {
  $('#pw-title').textContent = `كلمة مرور جديدة لـ ${username}`;
  $('#pw-id').value = id;
  $('#pw-new').value = '';
  openModal('pwModal');
}

async function savePw() {
  try {
    await api(`/api/users/${$('#pw-id').value}/password`, { method: 'POST', body: { password: $('#pw-new').value } });
    closeModal('pwModal');
    toast('تم تغيير كلمة المرور');
  } catch (e) { toast(e.message, 'err'); }
}

async function delUser(id, username) {
  if (!await confirmDlg(`حذف المستخدم ${username}؟`)) return;
  try {
    await api('/api/users/' + id, { method: 'DELETE' });
    toast('تم حذف المستخدم');
    setTimeout(() => location.reload(), 700);
  } catch (e) { toast(e.message, 'err'); }
}
</script>
@endsection
