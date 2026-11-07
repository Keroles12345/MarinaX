@extends('admin.layouts.app')
@section('title')صفحة الأدمن - السيارات والتوبيسات@endsection
@section('page_title')صفحة الأدمن - السيارات والتوبيسات@endsection
@section('content')

@if(session('status'))<div class="alert ok">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif

<div class="panel">
  <div class="panel-head">
    <h3>السيارات والتوبيسات المسجلة — {{ $vehicles->count() }}</h3>
    <a class="btn btn-dark" href="{{ route('accounting.suppliers') }}">إدارة الموردين</a>
    <a class="btn btn-dark" href="{{ route('accounting.drivers') }}">إدارة سائقي السيارات والأتوبيسات</a>
    @if(request()->boolean('include_archived'))
      <a class="btn btn-dark" href="{{ route('accounting.vehicles') }}">إخفاء المؤرشف</a>
    @else
      <a class="btn btn-dark" href="{{ route('accounting.vehicles', ['include_archived' => 1]) }}">عرض المؤرشف</a>
    @endif
    <button class="btn btn-gold" type="button" onclick="openVehicleModal('create-vehicle-modal')">＋ إضافة سيارة / توبيس</button>
  </div>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>السيارة / التوبيس</th><th>المنطقة</th><th>الكود</th><th>النوع</th><th>التشغيل</th><th>المورد</th><th>حساب الإيراد</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
    <tbody>
      @forelse($vehicles as $vehicle)
        <tr>
          <td>{{ $vehicle->name }}</td><td>{{ $vehicle->region?->name ?? 'غير محددة' }}</td><td class="num">{{ $vehicle->code }}</td><td>{{ $vehicle->vehicleType?->name ?? '—' }}</td>
          <td>{{ $vehicle->ownership_type === 'external' ? 'خارجي' : 'داخلي' }}</td>
          <td>{{ $vehicle->supplier?->name ?? '—' }}</td>
          <td>{{ $vehicle->revenueAccount?->acc_no }} — {{ $vehicle->revenueAccount?->name ?? 'بانتظار إنشاء الحساب' }}</td>
          <td>{{ $vehicle->status === 'active' ? 'نشطة' : 'غير نشطة' }}</td>
          <td>
            <div class="j-actions">
              <button class="btn btn-dark btn-sm" type="button" data-edit-vehicle
                data-id="{{ $vehicle->id }}" data-url="{{ route('accounting.vehicles.update', $vehicle) }}"
                data-name="{{ $vehicle->name }}" data-code="{{ $vehicle->code }}"
                data-type-id="{{ $vehicle->vehicle_type_id }}" data-ownership="{{ $vehicle->ownership_type }}"
                data-status="{{ $vehicle->status }}" data-color="{{ $vehicle->color }}" data-region="{{ $vehicle->region_id }}"
                data-codes="{{ $vehicle->codes }}" data-codee="{{ $vehicle->codee }}"
                data-supplier="{{ $vehicle->supplier_id }}" data-supplier-price="{{ $vehicle->supplier_price }}">تعديل</button>
              <form method="POST" action="{{ route('accounting.vehicles.destroy', $vehicle) }}" onsubmit="return confirm('سيتم أرشفة السيارة وإخفاؤها من الموارد النشطة مع الاحتفاظ بسجلاتها المحاسبية والتشغيلية. هل تريد المتابعة؟')">
                @csrf @method('DELETE')
                <button class="btn btn-red btn-sm" type="submit">حذف</button>
              </form>
            </div>
          </td>
        </tr>
      @empty<tr><td colspan="9" class="empty-state">لا توجد سيارات أو توبيسات مسجلة.</td></tr>@endforelse
    </tbody>
  </table></div>
</div>

<div class="modal-overlay" id="create-vehicle-modal" role="dialog" aria-modal="true" aria-labelledby="create-vehicle-title">
  <div class="modal wide">
    <div class="modal-head"><h3 id="create-vehicle-title">إضافة سيارة أو توبيس</h3><button class="modal-close" type="button" aria-label="إغلاق" onclick="closeVehicleModal('create-vehicle-modal')">×</button></div>
    <form method="POST" action="{{ route('accounting.vehicles.store') }}">
      @csrf
      <input type="hidden" name="_form_type" value="create">
      <div class="modal-body">
        <div class="grid-2">
          <div class="field"><label for="vehicle-name">اسم السيارة / التوبيس *</label><input id="vehicle-name" name="name" value="{{ old('_form_type') === 'create' ? old('name') : '' }}" required maxlength="100"></div>
          <div class="field"><label for="vehicle-code">الكود *</label><input id="vehicle-code" name="code" value="{{ old('_form_type') === 'create' ? old('code') : '' }}" required maxlength="50"></div>
          <div class="field"><label for="vehicle-type">النوع *</label>
            <select id="vehicle-type" name="vehicle_type_id" required>
              <option value="">اختر النوع</option>
              @foreach($vehicleTypes as $type)<option value="{{ $type->id }}" @selected(old('_form_type') === 'create' && old('vehicle_type_id') == $type->id)>{{ $type->name }}</option>@endforeach
            </select>
            @if($vehicleTypes->isEmpty())<small class="hint">لا توجد أنواع سيارات معرفة في النظام.</small>@endif
          </div>
          <div class="field"><label for="vehicle-region">المنطقة *</label><select id="vehicle-region" name="region_id" required><option value="">اختر المنطقة</option>@foreach($regions as $region)<option value="{{ $region->id }}" @selected(old('_form_type') === 'create' && old('region_id') == $region->id)>{{ $region->name }}</option>@endforeach</select></div>
          <div class="field"><label for="vehicle-ownership">التشغيل *</label>
            <select id="vehicle-ownership" name="ownership_type" required>
              <option value="internal" @selected(old('_form_type') === 'create' && old('ownership_type', 'internal') === 'internal')>داخلي</option>
              <option value="external" @selected(old('_form_type') === 'create' && old('ownership_type') === 'external')>خارجي</option>
            </select>
          </div>
          <div class="field vehicle-create-supplier-field"><label for="vehicle-supplier">المورد *</label>
            <select id="vehicle-supplier" name="supplier_id">
              <option value="">اختر المورد</option>
              @foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected(old('_form_type') === 'create' && old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>@endforeach
            </select>
          </div>
          <div class="field vehicle-create-supplier-field"><label for="vehicle-supplier-price">تكلفة المورد *</label><input id="vehicle-supplier-price" type="number" name="supplier_price" value="{{ old('_form_type') === 'create' ? old('supplier_price') : '' }}" min="0" step="0.01"></div>
          <div class="field"><label for="vehicle-status">الحالة *</label>
            <select id="vehicle-status" name="status" required>
              <option value="active" @selected(old('_form_type') !== 'create' || old('status', 'active') === 'active')>نشطة</option>
              <option value="inactive" @selected(old('_form_type') === 'create' && old('status') === 'inactive')>غير نشطة</option>
            </select>
          </div>
          <div class="field"><label for="vehicle-color">اللون</label><input id="vehicle-color" name="color" value="{{ old('_form_type') === 'create' ? old('color') : '' }}" maxlength="50"></div>
          <div class="field"><label for="vehicle-codes">الكود الإضافي</label><input id="vehicle-codes" name="codes" value="{{ old('_form_type') === 'create' ? old('codes') : '' }}" maxlength="50"></div>
          <div class="field"><label for="vehicle-codee">الوصف / الفئة</label><input id="vehicle-codee" name="codee" value="{{ old('_form_type') === 'create' ? old('codee') : '' }}" maxlength="50"></div>
        </div>
      </div>
      <div class="modal-foot">
        <span class="hint">سيُنشأ حساب الإيراد تحت 4103 للتشغيل الداخلي أو 4104 للخارجي.</span>
        <button class="btn btn-gold" type="submit" @disabled($vehicleTypes->isEmpty())>حفظ السيارة وإنشاء حسابها</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="edit-vehicle-modal" role="dialog" aria-modal="true" aria-labelledby="edit-vehicle-title">
  <div class="modal wide">
    <div class="modal-head"><h3 id="edit-vehicle-title">تعديل بيانات السيارة / التوبيس</h3><button class="modal-close" type="button" aria-label="إغلاق" onclick="closeVehicleModal('edit-vehicle-modal')">×</button></div>
    <form id="edit-vehicle-form" method="POST">
      @csrf @method('PUT')
      <input type="hidden" name="_form_type" value="edit">
      <input type="hidden" name="_resource_id" id="edit-vehicle-id">
      <div class="modal-body">
        <div class="grid-2">
          <div class="field"><label for="edit-vehicle-name">اسم السيارة / التوبيس *</label><input id="edit-vehicle-name" name="name" required maxlength="100"></div>
          <div class="field"><label for="edit-vehicle-code">الكود *</label><input id="edit-vehicle-code" name="code" required maxlength="50"></div>
          <div class="field"><label for="edit-vehicle-type">النوع *</label>
            <select id="edit-vehicle-type" name="vehicle_type_id" required><option value="">اختر النوع</option>
              @foreach($vehicleTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach
            </select>
          </div>
          <div class="field"><label for="edit-vehicle-region">المنطقة *</label><select id="edit-vehicle-region" name="region_id" required><option value="">اختر المنطقة</option>@foreach($regions as $region)<option value="{{ $region->id }}">{{ $region->name }}</option>@endforeach</select></div>
          <div class="field"><label for="edit-vehicle-ownership">التشغيل *</label><select id="edit-vehicle-ownership" name="ownership_type" required><option value="internal">داخلي</option><option value="external">خارجي</option></select></div>
          <div class="field vehicle-edit-supplier-field"><label for="edit-vehicle-supplier">المورد *</label>
            <select id="edit-vehicle-supplier" name="supplier_id"><option value="">اختر المورد</option>
              @foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach
            </select>
          </div>
          <div class="field vehicle-edit-supplier-field"><label for="edit-vehicle-supplier-price">تكلفة المورد *</label><input id="edit-vehicle-supplier-price" type="number" name="supplier_price" min="0" step="0.01"></div>
          <div class="field"><label for="edit-vehicle-status">الحالة *</label><select id="edit-vehicle-status" name="status" required><option value="active">نشطة</option><option value="inactive">غير نشطة / مؤرشفة</option></select></div>
          <div class="field"><label for="edit-vehicle-color">اللون</label><input id="edit-vehicle-color" name="color" maxlength="50"></div>
          <div class="field"><label for="edit-vehicle-codes">الكود الإضافي</label><input id="edit-vehicle-codes" name="codes" maxlength="50"></div>
          <div class="field"><label for="edit-vehicle-codee">الوصف / الفئة</label><input id="edit-vehicle-codee" name="codee" maxlength="50"></div>
        </div>
      </div>
      <div class="modal-foot">
        <span class="hint">سيتم تحديث اسم حساب الإيراد وموقعه حسب نوع التشغيل.</span>
        <button class="btn btn-dark" type="button" onclick="closeVehicleModal('edit-vehicle-modal')">إلغاء</button>
        <button class="btn btn-gold" type="submit">حفظ التعديلات</button>
      </div>
    </form>
  </div>
</div>

@endsection
@section('scripts')
<script>
function openVehicleModal(id) { document.getElementById(id).classList.add('open'); }
function closeVehicleModal(id) { document.getElementById(id).classList.remove('open'); }

function updateVehicleSupplierFields(prefix) {
  const idPrefix = prefix === 'create-' ? 'vehicle-' : 'edit-vehicle-';
  const external = document.getElementById(idPrefix + 'ownership').value === 'external';
  document.querySelectorAll('.vehicle-' + prefix + 'supplier-field').forEach(field => field.hidden = !external);
  document.getElementById(idPrefix + 'supplier').required = external;
  document.getElementById(idPrefix + 'supplier-price').required = external;
}

document.getElementById('vehicle-ownership').addEventListener('change', () => updateVehicleSupplierFields('create-'));
document.getElementById('edit-vehicle-ownership').addEventListener('change', () => updateVehicleSupplierFields('edit-'));
document.querySelectorAll('[data-edit-vehicle]').forEach(button => button.addEventListener('click', () => {
  const data = button.dataset;
  const form = document.getElementById('edit-vehicle-form');
  form.action = data.url;
  document.getElementById('edit-vehicle-id').value = data.id;
  document.getElementById('edit-vehicle-name').value = data.name;
  document.getElementById('edit-vehicle-code').value = data.code;
  document.getElementById('edit-vehicle-type').value = data.typeId;
  document.getElementById('edit-vehicle-region').value = data.region || '';
  document.getElementById('edit-vehicle-ownership').value = data.ownership;
  document.getElementById('edit-vehicle-status').value = data.status;
  document.getElementById('edit-vehicle-color').value = data.color || '';
  document.getElementById('edit-vehicle-codes').value = data.codes || '';
  document.getElementById('edit-vehicle-codee').value = data.codee || '';
  document.getElementById('edit-vehicle-supplier').value = data.supplier || '';
  document.getElementById('edit-vehicle-supplier-price').value = data.supplierPrice || '';
  updateVehicleSupplierFields('edit-');
  openVehicleModal('edit-vehicle-modal');
}));

updateVehicleSupplierFields('create-');
@if($errors->any() && old('_form_type') === 'create')
openVehicleModal('create-vehicle-modal');
@elseif($errors->any() && old('_form_type') === 'edit')
{
  const editButton = document.querySelector('[data-edit-vehicle][data-id="{{ old('_resource_id') }}"]');
  if (editButton) {
    editButton.click();
    @foreach(['name', 'code', 'vehicle_type_id', 'region_id', 'ownership_type', 'supplier_id', 'supplier_price', 'status', 'color', 'codes', 'codee'] as $field)
    if (document.querySelector('#edit-vehicle-form [name="{{ $field }}"]')) document.querySelector('#edit-vehicle-form [name="{{ $field }}"]').value = @json(old($field));
    @endforeach
    updateVehicleSupplierFields('edit-');
  }
}
@endif
</script>
@endsection
