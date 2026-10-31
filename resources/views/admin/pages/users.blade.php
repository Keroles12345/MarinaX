@extends('admin.layouts.app')
@section('title')المستخدمون@endsection
@section('page_title')إدارة المستخدمين@endsection
@section('content')

<div class="panel">
  <div class="panel-head">
    <h3>👥 إدارة المستخدمين — {{ $users->count() }} مستخدم</h3>
    <button class="btn btn-teal btn-sm" onclick="openModal('addModal')">➕ مستخدم جديد</button>
  </div>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>الاسم</th><th class="num">اسم الدخول</th><th>البريد الإلكتروني</th><th>الصلاحية</th><th>المناطق</th><th>سؤال الأمان</th><th class="num">تاريخ الإضافة</th><th class="num">إجراءات</th></tr></thead>
      <tbody>
        @forelse($users as $u)
        <tr>
          <td>{{ $u->name }}</td>
          <td class="num">{{ $u->username }}</td>
          <td>{{ $u->email }}</td>
          <td>@if($u->role === 'admin')<span class="badge posted">مدير النظام</span>
              @elseif($u->role === 'accountant')<span class="badge region">محاسب</span>
              @elseif($u->role === 'viewer')<span class="badge gray">مشاهدة فقط</span>
              @elseif($u->role === 'account_user')<span class="badge gray">موظف حسابات</span>
              @elseif($u->role === 'staff')<span class="badge region">موظف</span>
              @elseif($u->role === 'supervisor')<span class="badge region">مشرف</span>
              @else<span class="badge gray">عميل</span>@endif</td>
          <td>
            @if($u->role === 'staff')
              {{ $u->regions->pluck('name')->join('، ') ?: 'غير محددة' }}
              <button class="btn btn-sm btn-dark" data-user-action="regions"
                data-id="{{ $u->id }}" data-username="{{ $u->username }}"
                data-region-ids="{{ $u->regions->pluck('id')->implode(',') }}">تحديد</button>
            @else
              —
            @endif
          </td>
          <td>@if($u->security_question)<span class="badge posted" title="{{ $u->security_question }}">مُفعّل</span>
              @else<span class="badge draft">بدون سؤال</span>@endif</td>
          <td class="num">{{ $u->created_at?->format('Y-m-d H:i') }}</td>
          <td class="num">
            <button class="btn btn-sm btn-dark" data-user-action="edit"
              data-id="{{ $u->id }}" data-name="{{ $u->name }}" data-username="{{ $u->username }}"
              data-email="{{ $u->email }}" data-role="{{ $u->role }}">✏️ تعديل</button>
            <button class="btn btn-sm btn-dark" data-user-action="security"
              data-id="{{ $u->id }}" data-username="{{ $u->username }}"
              data-question="{{ $u->security_question }}">🛡️ سؤال أمان</button>
            <button class="btn btn-sm btn-teal" data-user-action="password"
              data-id="{{ $u->id }}" data-username="{{ $u->username }}">🔑 كلمة مرور</button>
            @if($u->id !== $user->id)
            <button class="btn btn-sm btn-red" data-user-action="delete"
              data-id="{{ $u->id }}" data-username="{{ $u->username }}">🗑️</button>
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="8" class="empty-state">لا يوجد مستخدمون لعرضهم</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="panel-body hint">
    💡 سؤال الأمان يُفعّل «نسيت كلمة المرور؟» لصاحب الحساب حتى يستعيد دخوله بنفسه عند نسيان كلمة المرور.
    <br>💡 تغيير كلمة المرور يحدّث دخول النظام المحاسبي ودخول الموظفين معًا.
  </div>
</div>

<!-- مودال إضافة مستخدم -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-head"><h3>مستخدم جديد</h3><button class="modal-close" data-close-modal>×</button></div>
    <div class="modal-body">
      <div class="field"><label>الاسم الكامل *</label><input id="u-name" type="text"></div>
      <div class="field"><label>اسم الدخول *</label><input id="u-user" type="text" placeholder="بالإنجليزية"></div>
      <div class="field"><label>البريد الإلكتروني *</label><input id="u-email" type="email" autocomplete="email"></div>
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

@endsection

@section('scripts')
<script>
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
