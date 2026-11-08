@extends('admin.layouts.app')
@section('title')إدارة الموردين@endsection
@section('page_title')إدارة الموردين@endsection
@section('content')

@if(session('status'))<div class="alert ok">{{ session('status') }}</div>@endif

<div class="panel">
  <div class="panel-head">
    <h3>الموردون — {{ $suppliers->count() }}</h3>
    @if(request()->boolean('include_archived'))
      <a class="btn btn-dark" href="{{ route('accounting.suppliers') }}">عرض النشط فقط</a>
    @else
      <a class="btn btn-dark" href="{{ route('accounting.suppliers', ['include_archived' => 1]) }}">عرض المؤرشف</a>
    @endif
    @systemPermission('suppliers', 'create')
    <button class="btn btn-gold" type="button" onclick="openSupplierModal('create-supplier-modal')">＋ إضافة مورد</button>
    @endsystemPermission
  </div>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr><th>اسم المورد</th><th>الهاتف</th><th>البريد الإلكتروني</th><th>العنوان</th><th>المراكب المرتبطة</th><th>الحالة</th><th>الإجراءات</th></tr>
      </thead>
      <tbody>
        @forelse($suppliers as $supplier)
          <tr>
            <td>{{ $supplier->name }}</td>
            <td dir="auto">{{ $supplier->phone ?: '—' }}</td>
            <td dir="auto">{{ $supplier->email ?: '—' }}</td>
            <td>{{ $supplier->address ?: '—' }}</td>
            <td class="num">{{ $supplier->boats_count }}</td>
            <td>{{ $supplier->status ? 'نشط' : 'مؤرشف' }}</td>
            <td>
              <div class="j-actions">
                @systemPermission('suppliers', 'update')
                <button class="btn btn-dark btn-sm" type="button" data-edit-supplier
                  data-id="{{ $supplier->id }}"
                  data-url="{{ route('accounting.suppliers.update', $supplier) }}"
                  data-name="{{ $supplier->name }}"
                  data-phone="{{ $supplier->phone }}"
                  data-email="{{ $supplier->email }}"
                  data-address="{{ $supplier->address }}"
                  data-status="{{ $supplier->status ? '1' : '0' }}">تعديل</button>
                @endsystemPermission
                @if($supplier->status)
                  @systemPermission('suppliers', 'delete')
                  <form method="POST" action="{{ route('accounting.suppliers.destroy', $supplier) }}"
                    onsubmit="return confirm('سيتم أرشفة المورد. ستظل السجلات والارتباطات السابقة محفوظة. هل تريد المتابعة؟')">
                    @csrf @method('DELETE')
                    <button class="btn btn-red btn-sm" type="submit">أرشفة</button>
                  </form>
                  @endsystemPermission
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="empty-state">لا يوجد موردون {{ request()->boolean('include_archived') ? 'مسجلون' : 'نشطون' }}.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@systemPermission('suppliers', 'create')
<div class="modal-overlay" id="create-supplier-modal" role="dialog" aria-modal="true" aria-labelledby="create-supplier-title">
  <div class="modal">
    <div class="modal-head">
      <h3 id="create-supplier-title">إضافة مورد</h3>
      <button class="modal-close" type="button" aria-label="إغلاق" onclick="closeSupplierModal('create-supplier-modal')">×</button>
    </div>
    <form method="POST" action="{{ route('accounting.suppliers.store') }}">
      @csrf
      <input type="hidden" name="_form_type" value="create">
      <div class="modal-body">
        @if($errors->any() && old('_form_type') === 'create')
          <div class="alert err">{{ $errors->first() }}</div>
        @endif
        <div class="field"><label for="supplier-name">اسم المورد *</label><input id="supplier-name" name="name" value="{{ old('_form_type') === 'create' ? old('name') : '' }}" required maxlength="255">@if(old('_form_type') === 'create') @error('name')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror @endif</div>
        <div class="grid-2">
          <div class="field"><label for="supplier-phone">الهاتف</label><input id="supplier-phone" name="phone" value="{{ old('_form_type') === 'create' ? old('phone') : '' }}" maxlength="50" dir="auto">@if(old('_form_type') === 'create') @error('phone')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror @endif</div>
          <div class="field"><label for="supplier-email">البريد الإلكتروني</label><input id="supplier-email" type="email" name="email" value="{{ old('_form_type') === 'create' ? old('email') : '' }}" maxlength="255" dir="auto">@if(old('_form_type') === 'create') @error('email')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror @endif</div>
        </div>
        <div class="field"><label for="supplier-address">العنوان</label><textarea id="supplier-address" name="address" rows="3" maxlength="2000">{{ old('_form_type') === 'create' ? old('address') : '' }}</textarea>@if(old('_form_type') === 'create') @error('address')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror @endif</div>
      </div>
      <div class="modal-foot">
        <button class="btn btn-gold" type="submit">حفظ المورد</button>
        <button class="btn btn-dark" type="button" onclick="closeSupplierModal('create-supplier-modal')">إلغاء</button>
      </div>
    </form>
  </div>
</div>
@endsystemPermission

@systemPermission('suppliers', 'update')
<div class="modal-overlay" id="edit-supplier-modal" role="dialog" aria-modal="true" aria-labelledby="edit-supplier-title">
  <div class="modal">
    <div class="modal-head">
      <h3 id="edit-supplier-title">تعديل بيانات المورد</h3>
      <button class="modal-close" type="button" aria-label="إغلاق" onclick="closeSupplierModal('edit-supplier-modal')">×</button>
    </div>
    <form id="edit-supplier-form" method="POST">
      @csrf @method('PUT')
      <input type="hidden" name="_form_type" value="edit">
      <input type="hidden" name="_resource_id" id="edit-supplier-id">
      <input type="hidden" name="_include_archived" value="{{ request()->boolean('include_archived') ? 1 : 0 }}">
      <div class="modal-body">
        @if($errors->any() && old('_form_type') === 'edit')
          <div class="alert err">{{ $errors->first() }}</div>
        @endif
        <div class="field"><label for="edit-supplier-name">اسم المورد *</label><input id="edit-supplier-name" name="name" required maxlength="255">@if(old('_form_type') === 'edit') @error('name')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror @endif</div>
        <div class="grid-2">
          <div class="field"><label for="edit-supplier-phone">الهاتف</label><input id="edit-supplier-phone" name="phone" maxlength="50" dir="auto">@if(old('_form_type') === 'edit') @error('phone')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror @endif</div>
          <div class="field"><label for="edit-supplier-email">البريد الإلكتروني</label><input id="edit-supplier-email" type="email" name="email" maxlength="255" dir="auto">@if(old('_form_type') === 'edit') @error('email')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror @endif</div>
        </div>
        <div class="field"><label for="edit-supplier-address">العنوان</label><textarea id="edit-supplier-address" name="address" rows="3" maxlength="2000"></textarea>@if(old('_form_type') === 'edit') @error('address')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror @endif</div>
        <div class="field"><label for="edit-supplier-status">الحالة</label>
          <select id="edit-supplier-status" name="status" required>
            <option value="1">نشط</option>
            <option value="0">مؤرشف</option>
          </select>
          @if(old('_form_type') === 'edit') @error('status')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror @endif
        </div>
      </div>
      <div class="modal-foot">
        <button class="btn btn-gold" type="submit">حفظ التعديلات</button>
        <button class="btn btn-dark" type="button" onclick="closeSupplierModal('edit-supplier-modal')">إلغاء</button>
      </div>
    </form>
  </div>
</div>
@endsystemPermission
@endsection

@section('scripts')
<script>
function openSupplierModal(id) { document.getElementById(id).classList.add('open'); }
function closeSupplierModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('[data-edit-supplier]').forEach(button => button.addEventListener('click', () => {
  const data = button.dataset;
  const form = document.getElementById('edit-supplier-form');
  form.action = data.url;
  document.getElementById('edit-supplier-id').value = data.id;
  document.getElementById('edit-supplier-name').value = data.name || '';
  document.getElementById('edit-supplier-phone').value = data.phone || '';
  document.getElementById('edit-supplier-email').value = data.email || '';
  document.getElementById('edit-supplier-address').value = data.address || '';
  document.getElementById('edit-supplier-status').value = data.status;
  openSupplierModal('edit-supplier-modal');
}));

@if($errors->any() && old('_form_type') === 'create')
openSupplierModal('create-supplier-modal');
@elseif($errors->any() && old('_form_type') === 'edit')
{
  const form = document.getElementById('edit-supplier-form');
  const resourceId = @json(old('_resource_id'));
  const supplier = [...document.querySelectorAll('[data-edit-supplier]')]
    .find(button => button.dataset.id === String(resourceId));
  if (supplier) supplier.click();
  @foreach(['name', 'phone', 'email', 'address', 'status'] as $field)
  if (form.querySelector('[name="{{ $field }}"]')) form.querySelector('[name="{{ $field }}"]').value = @json(old($field));
  @endforeach
  openSupplierModal('edit-supplier-modal');
}
@endif
</script>
@endsection
