@extends('admin.layouts.app')
@section('title')إدارة الشركات@endsection
@section('page_title')إدارة الشركات@endsection
@section('content')

@if(session('status'))<div class="alert ok">{{ session('status') }}</div>@endif

<div class="panel">
  <div class="panel-head">
    <h3>الشركات — {{ $companies->count() }}</h3>
    @if(request()->boolean('include_archived'))
      <a class="btn btn-dark" href="{{ route('accounting.companies') }}">عرض النشط فقط</a>
    @else
      <a class="btn btn-dark" href="{{ route('accounting.companies', ['include_archived' => 1]) }}">عرض الموقوف</a>
    @endif
    @systemPermission('companies', 'create')
    <button class="btn btn-gold" type="button" onclick="openCompanyModal('create-company-modal')">＋ إضافة شركة</button>
    @endsystemPermission
  </div>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>اسم الشركة</th><th>البريد الإلكتروني</th><th>الهاتف</th><th>العنوان</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
      <tbody>
        @forelse($companies as $company)
          <tr>
            <td>{{ $company->name }}</td>
            <td dir="auto">{{ $company->email ?: '—' }}</td>
            <td dir="auto">{{ $company->phone ?: '—' }}</td>
            <td>{{ $company->address ?: '—' }}</td>
            <td>{{ $company->status ? 'نشطة' : 'موقوفة' }}</td>
            <td><div class="j-actions">
              @systemPermission('companies', 'update')
              <button class="btn btn-dark btn-sm" type="button" data-edit-company
                data-id="{{ $company->id }}" data-url="{{ route('accounting.companies.update', $company) }}"
                data-name="{{ $company->name }}" data-email="{{ $company->email }}"
                data-phone="{{ $company->phone }}" data-address="{{ $company->address }}"
                data-status="{{ $company->status ? '1' : '0' }}">تعديل</button>
              @endsystemPermission
              @if($company->status)
                @systemPermission('companies', 'delete')
                <form method="POST" action="{{ route('accounting.companies.destroy', $company) }}" onsubmit="return confirm('سيتم إيقاف الشركة مع الاحتفاظ بالحجوزات والأسعار المرتبطة. هل تريد المتابعة؟')">
                  @csrf @method('DELETE')<button class="btn btn-red btn-sm" type="submit">إيقاف</button>
                </form>
                @endsystemPermission
              @endif
            </div></td>
          </tr>
        @empty
          <tr><td colspan="6" class="empty-state">لا توجد شركات {{ request()->boolean('include_archived') ? 'مسجلة' : 'نشطة' }}.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@systemPermission('companies', 'create')
<div class="modal-overlay" id="create-company-modal" role="dialog" aria-modal="true" aria-labelledby="create-company-title">
  <div class="modal">
    <div class="modal-head"><h3 id="create-company-title">إضافة شركة</h3><button class="modal-close" type="button" aria-label="إغلاق" onclick="closeCompanyModal('create-company-modal')">×</button></div>
    <form method="POST" action="{{ route('accounting.companies.store') }}">
      @csrf<input type="hidden" name="_form_type" value="create">
      <div class="modal-body">
        @if($errors->any() && old('_form_type') === 'create')<div class="alert err">{{ $errors->first() }}</div>@endif
        <div class="field"><label>اسم الشركة *</label><input name="name" required maxlength="255" value="{{ old('_form_type') === 'create' ? old('name') : '' }}">@if(old('_form_type') === 'create') @error('name')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror @endif</div>
        <div class="grid-2">
          <div class="field"><label>البريد الإلكتروني</label><input type="email" name="email" maxlength="255" dir="auto" value="{{ old('_form_type') === 'create' ? old('email') : '' }}">@if(old('_form_type') === 'create') @error('email')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror @endif</div>
          <div class="field"><label>الهاتف</label><input name="phone" maxlength="50" dir="auto" value="{{ old('_form_type') === 'create' ? old('phone') : '' }}">@if(old('_form_type') === 'create') @error('phone')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror @endif</div>
        </div>
        <div class="field"><label>العنوان</label><textarea name="address" rows="3" maxlength="2000">{{ old('_form_type') === 'create' ? old('address') : '' }}</textarea>@if(old('_form_type') === 'create') @error('address')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror @endif</div>
      </div>
      <div class="modal-foot"><button class="btn btn-gold" type="submit">حفظ الشركة</button><button class="btn btn-dark" type="button" onclick="closeCompanyModal('create-company-modal')">إلغاء</button></div>
    </form>
  </div>
</div>
@endsystemPermission

@systemPermission('companies', 'update')
<div class="modal-overlay" id="edit-company-modal" role="dialog" aria-modal="true" aria-labelledby="edit-company-title">
  <div class="modal">
    <div class="modal-head"><h3 id="edit-company-title">تعديل بيانات الشركة</h3><button class="modal-close" type="button" aria-label="إغلاق" onclick="closeCompanyModal('edit-company-modal')">×</button></div>
    <form id="edit-company-form" method="POST">
      @csrf @method('PUT')
      <input type="hidden" name="_form_type" value="edit"><input type="hidden" name="_resource_id" id="edit-company-id">
      <input type="hidden" name="_include_archived" value="{{ request()->boolean('include_archived') ? 1 : 0 }}">
      <div class="modal-body">
        @if($errors->any() && old('_form_type') === 'edit')<div class="alert err">{{ $errors->first() }}</div>@endif
        <div class="field"><label>اسم الشركة *</label><input id="edit-company-name" name="name" required maxlength="255"></div>
        <div class="grid-2">
          <div class="field"><label>البريد الإلكتروني</label><input id="edit-company-email" type="email" name="email" maxlength="255" dir="auto"></div>
          <div class="field"><label>الهاتف</label><input id="edit-company-phone" name="phone" maxlength="50" dir="auto"></div>
        </div>
        <div class="field"><label>العنوان</label><textarea id="edit-company-address" name="address" rows="3" maxlength="2000"></textarea></div>
        <div class="field"><label>الحالة</label><select id="edit-company-status" name="status"><option value="1">نشطة</option><option value="0">موقوفة</option></select></div>
      </div>
      <div class="modal-foot"><button class="btn btn-gold" type="submit">حفظ التعديلات</button><button class="btn btn-dark" type="button" onclick="closeCompanyModal('edit-company-modal')">إلغاء</button></div>
    </form>
  </div>
</div>
@endsystemPermission
@endsection

@section('scripts')
<script>
function openCompanyModal(id) { document.getElementById(id).classList.add('open'); }
function closeCompanyModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('[data-edit-company]').forEach(button => button.addEventListener('click', () => {
  const data = button.dataset;
  const form = document.getElementById('edit-company-form');
  form.action = data.url;
  document.getElementById('edit-company-id').value = data.id;
  document.getElementById('edit-company-name').value = data.name;
  document.getElementById('edit-company-email').value = data.email || '';
  document.getElementById('edit-company-phone').value = data.phone || '';
  document.getElementById('edit-company-address').value = data.address || '';
  document.getElementById('edit-company-status').value = data.status;
  openCompanyModal('edit-company-modal');
}));

@if($errors->any() && old('_form_type') === 'create')
openCompanyModal('create-company-modal');
@elseif($errors->any() && old('_form_type') === 'edit')
{
  const resourceId = @json(old('_resource_id'));
  const button = [...document.querySelectorAll('[data-edit-company]')].find(item => item.dataset.id === String(resourceId));
  if (button) button.click();
  @foreach(['name', 'email', 'phone', 'address', 'status'] as $field)
  const field{{ $loop->index }} = document.querySelector(`#edit-company-form [name="{{ $field }}"]`);
  if (field{{ $loop->index }}) field{{ $loop->index }}.value = @json(old($field));
  @endforeach
}
@endif
</script>
@endsection
