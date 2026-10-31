@extends('admin.layouts.app')
@section('title')صفحة الأدمن - المراكب@endsection
@section('page_title')صفحة الأدمن - المراكب@endsection
@section('content')

@if(session('status'))<div class="alert ok">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif

<div class="panel">
  <div class="panel-head">
    <h3>المراكب المسجلة — {{ $boats->count() }}</h3>
    <a class="btn btn-dark" href="{{ route('accounting.suppliers') }}">إدارة الموردين</a>
    <a class="btn btn-dark" href="{{ route('accounting.boat-drivers') }}">إدارة سائقين المراكب</a>
    @if(request()->boolean('include_archived'))
      <a class="btn btn-dark" href="{{ route('accounting.boats') }}">إخفاء المؤرشف</a>
    @else
      <a class="btn btn-dark" href="{{ route('accounting.boats', ['include_archived' => 1]) }}">عرض المؤرشف</a>
    @endif
    <button class="btn btn-gold" type="button" onclick="openBoatModal('create-boat-modal')">＋ إضافة مركب</button>
  </div>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>المركب</th><th>المنطقة</th><th>الكود</th><th>الملكية</th><th>المورد</th><th>حساب الإيراد</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
    <tbody>
      @forelse($boats as $boat)
        <tr>
          <td>{{ $boat->name }}</td><td>{{ $boat->region?->name ?? 'غير محددة' }}</td><td class="num">{{ $boat->code }}</td>
          <td>{{ $boat->ownership_type === 'external' ? 'خارجي' : 'داخلي' }}</td><td>{{ $boat->supplier?->name ?? '—' }}</td>
          <td>{{ $boat->revenueAccount?->acc_no }} — {{ $boat->revenueAccount?->name ?? 'بانتظار إنشاء الحساب' }}</td>
          <td>{{ $boat->is_active ? 'نشط' : 'غير نشط' }}</td>
          <td>
            <div class="j-actions">
              <button class="btn btn-dark btn-sm" type="button" data-edit-boat
                data-id="{{ $boat->id }}" data-url="{{ route('accounting.boats.update', $boat) }}"
                data-name="{{ $boat->name }}" data-code="{{ $boat->code }}"
                data-capacity="{{ $boat->capacity }}" data-ownership="{{ $boat->ownership_type }}"
                data-type="{{ $boat->type }}" data-registration="{{ $boat->registration_number }}"
                data-supplier="{{ $boat->supplier_id }}" data-supplier-price="{{ $boat->supplier_price }}"
                data-region="{{ $boat->region_id }}"
                data-active="{{ $boat->is_active ? '1' : '0' }}">تعديل</button>
              <form method="POST" action="{{ route('accounting.boats.destroy', $boat) }}" onsubmit="return confirm('سيتم أرشفة المركب وإخفاؤه من الموارد النشطة مع الاحتفاظ بسجلاته المحاسبية والتشغيلية. هل تريد المتابعة؟')">
                @csrf @method('DELETE')
                <button class="btn btn-red btn-sm" type="submit">حذف</button>
              </form>
            </div>
          </td>
        </tr>
      @empty<tr><td colspan="8" class="empty-state">لا توجد مراكب مسجلة.</td></tr>@endforelse
    </tbody>
  </table></div>
</div>

<div class="modal-overlay" id="create-boat-modal" role="dialog" aria-modal="true" aria-labelledby="create-boat-title">
  <div class="modal wide">
    <div class="modal-head"><h3 id="create-boat-title">إضافة مركب</h3><button class="modal-close" type="button" aria-label="إغلاق" onclick="closeBoatModal('create-boat-modal')">×</button></div>
    <form method="POST" action="{{ route('accounting.boats.store') }}">
      @csrf
      <input type="hidden" name="_form_type" value="create">
      <div class="modal-body">
        <div class="grid-2">
          <div class="field"><label for="boat-name">اسم المركب *</label><input id="boat-name" name="name" value="{{ old('_form_type') === 'create' ? old('name') : '' }}" required maxlength="100"></div>
          <div class="field"><label for="boat-code">كود المركب *</label><input id="boat-code" name="code" value="{{ old('_form_type') === 'create' ? old('code') : '' }}" required maxlength="50"></div>
          <div class="field"><label for="boat-capacity">السعة *</label><input id="boat-capacity" type="number" name="capacity" value="{{ old('_form_type') === 'create' ? old('capacity', 1) : 1 }}" required min="1"></div>
          <div class="field"><label for="boat-region">المنطقة *</label><select id="boat-region" name="region_id" required><option value="">اختر المنطقة</option>@foreach($regions as $region)<option value="{{ $region->id }}" @selected(old('_form_type') === 'create' && old('region_id') == $region->id)>{{ $region->name }}</option>@endforeach</select></div>
          <div class="field"><label for="boat-ownership">ملكية المركب *</label>
            <select id="boat-ownership" name="ownership_type" required>
              <option value="internal" @selected(old('_form_type') === 'create' && old('ownership_type', 'internal') === 'internal')>داخلي</option>
              <option value="external" @selected(old('_form_type') === 'create' && old('ownership_type') === 'external')>خارجي</option>
            </select>
          </div>
          <div class="field"><label for="boat-type">نوع المركب</label><input id="boat-type" name="type" value="{{ old('_form_type') === 'create' ? old('type') : '' }}" maxlength="50" placeholder="لنش، يخت..."></div>
          <div class="field"><label for="boat-registration">رقم التسجيل</label><input id="boat-registration" name="registration_number" value="{{ old('_form_type') === 'create' ? old('registration_number') : '' }}" maxlength="100"></div>
          <div class="field boat-create-supplier-field"><label for="boat-supplier">المورد *</label>
            <select id="boat-supplier" name="supplier_id">
              <option value="">اختر المورد</option>
              @foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected(old('_form_type') === 'create' && old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>@endforeach
            </select>
          </div>
          <div class="field boat-create-supplier-field"><label for="boat-supplier-price">تكلفة المورد *</label><input id="boat-supplier-price" type="number" name="supplier_price" value="{{ old('_form_type') === 'create' ? old('supplier_price') : '' }}" min="0" step="0.01"></div>
        </div>
      </div>
      <div class="modal-foot">
        <span class="hint">سيُنشأ حساب الإيراد تحت 4103 للداخلي أو 4104 للخارجي.</span>
        <button class="btn btn-gold" type="submit">حفظ المركب</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="edit-boat-modal" role="dialog" aria-modal="true" aria-labelledby="edit-boat-title">
  <div class="modal wide">
    <div class="modal-head"><h3 id="edit-boat-title">تعديل بيانات المركب</h3><button class="modal-close" type="button" aria-label="إغلاق" onclick="closeBoatModal('edit-boat-modal')">×</button></div>
    <form id="edit-boat-form" method="POST">
      @csrf @method('PUT')
      <input type="hidden" name="_form_type" value="edit">
      <input type="hidden" name="_resource_id" id="edit-boat-id">
      <div class="modal-body">
        <div class="grid-2">
          <div class="field"><label for="edit-boat-name">اسم المركب *</label><input id="edit-boat-name" name="name" required maxlength="100"></div>
          <div class="field"><label for="edit-boat-code">كود المركب *</label><input id="edit-boat-code" name="code" required maxlength="50"></div>
          <div class="field"><label for="edit-boat-capacity">السعة *</label><input id="edit-boat-capacity" type="number" name="capacity" required min="1"></div>
          <div class="field"><label for="edit-boat-region">المنطقة *</label><select id="edit-boat-region" name="region_id" required><option value="">اختر المنطقة</option>@foreach($regions as $region)<option value="{{ $region->id }}">{{ $region->name }}</option>@endforeach</select></div>
          <div class="field"><label for="edit-boat-ownership">ملكية المركب *</label>
            <select id="edit-boat-ownership" name="ownership_type" required><option value="internal">داخلي</option><option value="external">خارجي</option></select>
          </div>
          <div class="field"><label for="edit-boat-type">نوع المركب</label><input id="edit-boat-type" name="type" maxlength="50"></div>
          <div class="field"><label for="edit-boat-registration">رقم التسجيل</label><input id="edit-boat-registration" name="registration_number" maxlength="100"></div>
          <div class="field boat-edit-supplier-field"><label for="edit-boat-supplier">المورد *</label>
            <select id="edit-boat-supplier" name="supplier_id"><option value="">اختر المورد</option>
              @foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach
            </select>
          </div>
          <div class="field boat-edit-supplier-field"><label for="edit-boat-supplier-price">تكلفة المورد *</label><input id="edit-boat-supplier-price" type="number" name="supplier_price" min="0" step="0.01"></div>
          <div class="field"><label for="edit-boat-active">الحالة *</label><select id="edit-boat-active" name="is_active" required><option value="1">نشط</option><option value="0">غير نشط / مؤرشف</option></select></div>
        </div>
      </div>
      <div class="modal-foot">
        <span class="hint">سيتم تحديث اسم حساب الإيراد وموقعه حسب الملكية.</span>
        <button class="btn btn-dark" type="button" onclick="closeBoatModal('edit-boat-modal')">إلغاء</button>
        <button class="btn btn-gold" type="submit">حفظ التعديلات</button>
      </div>
    </form>
  </div>
</div>

@endsection
@section('scripts')
<script>
function openBoatModal(id) { document.getElementById(id).classList.add('open'); }
function closeBoatModal(id) { document.getElementById(id).classList.remove('open'); }

function updateBoatSupplierFields(prefix) {
  const idPrefix = prefix === 'create-' ? 'boat-' : 'edit-boat-';
  const external = document.getElementById(idPrefix + 'ownership').value === 'external';
  document.querySelectorAll('.boat-' + prefix + 'supplier-field').forEach(field => field.hidden = !external);
  document.getElementById(idPrefix + 'supplier').required = external;
  document.getElementById(idPrefix + 'supplier-price').required = external;
}

document.getElementById('boat-ownership').addEventListener('change', () => updateBoatSupplierFields('create-'));
document.getElementById('edit-boat-ownership').addEventListener('change', () => updateBoatSupplierFields('edit-'));
document.querySelectorAll('[data-edit-boat]').forEach(button => button.addEventListener('click', () => {
  const data = button.dataset;
  const form = document.getElementById('edit-boat-form');
  form.action = data.url;
  document.getElementById('edit-boat-id').value = data.id;
  document.getElementById('edit-boat-name').value = data.name;
  document.getElementById('edit-boat-code').value = data.code;
  document.getElementById('edit-boat-capacity').value = data.capacity;
  document.getElementById('edit-boat-region').value = data.region || '';
  document.getElementById('edit-boat-ownership').value = data.ownership;
  document.getElementById('edit-boat-type').value = data.type || '';
  document.getElementById('edit-boat-registration').value = data.registration || '';
  document.getElementById('edit-boat-supplier').value = data.supplier || '';
  document.getElementById('edit-boat-supplier-price').value = data.supplierPrice || '';
  document.getElementById('edit-boat-active').value = data.active;
  updateBoatSupplierFields('edit-');
  openBoatModal('edit-boat-modal');
}));
updateBoatSupplierFields('create-');

@if($errors->any() && old('_form_type') === 'create')
openBoatModal('create-boat-modal');
@elseif($errors->any() && old('_form_type') === 'edit')
{
  const editButton = document.querySelector('[data-edit-boat][data-id="{{ old('_resource_id') }}"]');
  if (editButton) {
    editButton.click();
    @foreach(['name', 'code', 'capacity', 'region_id', 'ownership_type', 'type', 'registration_number', 'supplier_id', 'supplier_price', 'is_active'] as $field)
    if (document.querySelector('#edit-boat-form [name="{{ $field }}"]')) document.querySelector('#edit-boat-form [name="{{ $field }}"]').value = @json(old($field));
    @endforeach
    updateBoatSupplierFields('edit-');
  }
}
@endif
</script>
@endsection
