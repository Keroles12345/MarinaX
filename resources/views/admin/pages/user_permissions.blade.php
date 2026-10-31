@extends('admin.layouts.app')
@section('title')صلاحيات المستخدمين@endsection
@section('page_title')صلاحيات المستخدمين@endsection
@section('content')
<style>
  .permissions-page{display:grid;gap:18px}
  .permissions-intro{color:#667085;margin:0}
  .permissions-panel{background:#fff;border:1px solid #e3e8ef;border-radius:14px;overflow:hidden}
  .permissions-panel-head{padding:18px 20px;border-bottom:1px solid #edf0f4}
  .permissions-panel-head h3{margin:0;font-size:17px}
  .permissions-panel-head p{margin:5px 0 0;color:#667085;font-size:13px}
  .permissions-user-form{padding:18px 20px;display:grid;gap:8px;max-width:620px}
  .permissions-label{font-size:13px;font-weight:700;color:#344054}
  .permissions-select,.permissions-search{width:100%;padding:11px 12px;border:1px solid #d7dee8;border-radius:9px;background:#fff;color:#172033}
  .permissions-select:focus,.permissions-search:focus{outline:2px solid #b9d9d5;border-color:#147d73}
  .permissions-alert{padding:12px 16px;border-radius:9px}
  .permissions-alert.success{background:#eaf8f0;color:#176b3a}
  .permissions-alert.error{background:#fff1f0;color:#a61b1b}
  .permissions-user{display:flex;align-items:center;gap:13px}
  .permissions-avatar{width:46px;height:46px;border-radius:50%;display:grid;place-items:center;background:#e8f3f2;color:#147d73;font-weight:800;font-size:18px}
  .permissions-user p{margin:4px 0 0;color:#667085;font-size:13px}
  .permissions-tools{padding:14px 20px;display:grid;grid-template-columns:minmax(200px,1fr) auto;gap:12px;align-items:center;background:#f8fafc;border-bottom:1px solid #edf0f4}
  .permissions-select-all{display:flex;align-items:center;gap:8px;font-weight:600;font-size:13px;color:#344054;white-space:nowrap}
  .permissions-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;padding:20px}
  .perm-item{display:flex;align-items:center;gap:11px;min-height:48px;padding:11px 13px;border:1px solid #e3e8ef;border-radius:10px;cursor:pointer;transition:.15s}
  .perm-item:hover{border-color:#9ac9c3;background:#f5fbfa}
  .perm-item:has(input:checked){border-color:#147d73;background:#eef8f6}
  .perm-item input,.permissions-select-all input{width:17px;height:17px;accent-color:#147d73}
  .perm-name{font-weight:600;font-size:13px;color:#344054;direction:ltr;text-align:left}
  .permissions-footer{display:flex;justify-content:flex-start;gap:10px;padding:15px 20px;background:#f8fafc;border-top:1px solid #edf0f4}
  .permissions-button{border:0;border-radius:9px;padding:10px 18px;background:#147d73;color:#fff;font-weight:700;cursor:pointer}
  .permissions-button:hover{background:#116b63}
  .permissions-cancel{display:inline-flex;align-items:center;border:1px solid #d0d5dd;border-radius:9px;padding:9px 16px;color:#344054;text-decoration:none;background:#fff}
  .permissions-empty{text-align:center;padding:42px 20px;color:#667085}
  .permissions-empty strong{display:block;color:#344054;margin-bottom:6px}
  @media(max-width:600px){.permissions-tools{grid-template-columns:1fr}.permissions-grid{grid-template-columns:1fr;padding:14px}.permissions-user-form{padding:14px}}
</style>

<div class="permissions-page">
  <p class="permissions-intro">اختار المستخدم وحدد الصلاحيات التي يحتاجها للوصول إلى وظائف النظام.</p>

  @if(session('success'))<div class="permissions-alert success">{{ session('success') }}</div>@endif
  @if($errors->any())
    <div class="permissions-alert error"><ul style="margin:0;padding-right:20px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
  @endif

  <section class="permissions-panel">
    <div class="permissions-panel-head">
      <h3>اختيار المستخدم</h3>
      <p>ابحث عن حساب لعرض صلاحياته وتعديلها.</p>
    </div>
    <form class="permissions-user-form" method="GET" action="{{ route('admin.user-permissions') }}">
      <label class="permissions-label" for="user">المستخدم</label>
      <select class="permissions-select" name="user" id="user" onchange="this.form.submit()">
        <option value="">اختار مستخدم</option>
        @foreach($users as $item)
          <option value="{{ $item->id }}" @selected($selectedUser && $selectedUser->id === $item->id)>
            {{ $item->name }} — {{ $item->email }} ({{ $item->role }})
          </option>
        @endforeach
      </select>
    </form>
  </section>

  @if($selectedUser)
    <form class="permissions-panel" method="POST" action="{{ route('admin.user-permissions.update') }}">
      @csrf
      <input type="hidden" name="user_id" value="{{ $selectedUser->id }}">
      <div class="permissions-panel-head">
        <div class="permissions-user">
          <div class="permissions-avatar">{{ mb_substr($selectedUser->name, 0, 1) }}</div>
          <div><h3>{{ $selectedUser->name }}</h3><p>{{ $selectedUser->email }} · {{ $selectedUser->role }}</p></div>
        </div>
      </div>
      <div class="permissions-tools">
        <input class="permissions-search" type="search" id="permSearch" placeholder="ابحث عن صلاحية...">
        <label class="permissions-select-all"><input type="checkbox" id="selectAll"> تحديد كل النتائج الظاهرة</label>
      </div>
      <div class="permissions-grid">
        @forelse($permissions as $permission)
          <label class="perm-item" data-perm="{{ strtolower($permission->name) }}">
            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="perm-checkbox" @checked(in_array($permission->id, $assignedPermissionIds, true))>
            <span class="perm-name">{{ $permission->name }}</span>
          </label>
        @empty
          <p class="permissions-intro">لا توجد صلاحيات مسجلة للـ guard الافتراضي (web).</p>
        @endforelse
      </div>
      <div class="permissions-footer">
        <a class="permissions-cancel" href="{{ route('admin.user-permissions') }}">إلغاء</a>
        <button class="permissions-button" type="submit">حفظ الصلاحيات</button>
      </div>
    </form>
  @else
    <section class="permissions-panel permissions-empty">
      <strong>لم يتم اختيار مستخدم</strong>
      اختار مستخدمًا من القائمة لعرض صلاحياته.
    </section>
  @endif
</div>

@if($selectedUser)
<script>
(() => {
  const items = [...document.querySelectorAll('.perm-item')];
  const selectAll = document.getElementById('selectAll');
  const search = document.getElementById('permSearch');
  const visibleCheckboxes = () => items.filter(item => !item.hidden).map(item => item.querySelector('.perm-checkbox'));

  selectAll.addEventListener('change', () => {
    visibleCheckboxes().forEach(checkbox => { checkbox.checked = selectAll.checked; });
  });
  items.forEach(item => item.querySelector('.perm-checkbox').addEventListener('change', () => {
    const visible = visibleCheckboxes();
    selectAll.checked = visible.length > 0 && visible.every(checkbox => checkbox.checked);
  }));
  search.addEventListener('input', () => {
    const query = search.value.trim().toLowerCase();
    items.forEach(item => { item.hidden = !item.dataset.perm.includes(query); });
    const visible = visibleCheckboxes();
    selectAll.checked = visible.length > 0 && visible.every(checkbox => checkbox.checked);
  });
})();
</script>
@endif
@endsection
