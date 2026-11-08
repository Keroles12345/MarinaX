@extends('admin.layouts.app')
@section('title')سائقو السيارات والأتوبيسات@endsection
@section('page_title')سائقو السيارات والأتوبيسات@endsection
@section('content')

@if(session('status'))<div class="alert ok">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
@php($visibleDrivers = request()->boolean('include_archived') ? $drivers : $drivers->where('status', '!=', 'inactive'))

<div class="panel">
  <div class="panel-head">
    <h3>سائقو السيارات والأتوبيسات — {{ $visibleDrivers->count() }}</h3>
    @if(request()->boolean('include_archived'))
      <a class="btn btn-dark" href="{{ route('accounting.drivers') }}">إخفاء الموقوف</a>
    @else
      <a class="btn btn-dark" href="{{ route('accounting.drivers', ['include_archived' => 1]) }}">عرض الموقوف</a>
    @endif
    @systemPermission('drivers', 'create')
    <button class="btn btn-gold" type="button" onclick="openDriverModal('create-driver-modal')">＋ إضافة سائق سيارة / أتوبيس</button>
    @endsystemPermission
  </div>
  <div class="panel-body hint">اربط السائق بسيارة أو أتوبيس نشط ليظهر ضمن خيارات التشغيل اليومية.</div>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>السائق</th><th>الهاتف</th><th>رقم الرخصة</th><th>السيارات / الأتوبيسات</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
    <tbody>
      @forelse($visibleDrivers as $driver)
        @php($vehicleIds = $driver->vehicleDrivers->pluck('vehicle_id')->map(fn ($id) => (int) $id)->values())
        <tr>
          <td>{{ $driver->name }}</td><td>{{ $driver->phone ?: '—' }}</td><td>{{ $driver->license_number ?: '—' }}</td>
          <td>{{ $driver->vehicleDrivers->map(fn ($link) => $link->vehicle?->name)->filter()->implode('، ') ?: 'غير مربوط' }}</td>
          <td>{{ $driver->status ?: 'متاح' }}</td>
          <td><div class="j-actions">
            @systemPermission('drivers', 'update')
            <button class="btn btn-dark btn-sm" type="button" data-edit-driver
              data-url="{{ route('accounting.drivers.update', $driver) }}"
              data-name="{{ $driver->name }}" data-phone="{{ $driver->phone }}" data-email="{{ $driver->email }}"
              data-license="{{ $driver->license_number }}" data-license-date="{{ $driver->LicenseDate?->format('Y-m-d') }}"
              data-leave="{{ $driver->degreeOfLeave }}" data-status="{{ $driver->status }}"
              data-vehicles="{{ $vehicleIds->toJson() }}">تعديل</button>
            @endsystemPermission
            @if($driver->status === 'inactive')
              @systemPermission('drivers', 'update')
              <form method="POST" action="{{ route('accounting.drivers.restore', $driver) }}">@csrf @method('PUT')<button class="btn btn-gold btn-sm" type="submit">تفعيل</button></form>
              @endsystemPermission
            @else
              @systemPermission('drivers', 'delete')
              <form method="POST" action="{{ route('accounting.drivers.destroy', $driver) }}" onsubmit="return confirm('سيتم إيقاف السائق وفك ربطه بالسيارات والأتوبيسات. متابعة؟')">
                @csrf @method('DELETE')<button class="btn btn-red btn-sm" type="submit">إيقاف</button>
              </form>
              @endsystemPermission
            @endif
          </div></td>
        </tr>
      @empty<tr><td colspan="6" class="empty-state">لا يوجد سائقو سيارات أو أتوبيسات.</td></tr>@endforelse
    </tbody>
  </table></div>
</div>

@systemPermission('drivers', 'create')
<div class="modal-overlay" id="create-driver-modal" role="dialog" aria-modal="true" aria-labelledby="create-driver-title">
  <div class="modal wide">
    <div class="modal-head"><h3 id="create-driver-title">إضافة سائق سيارة أو أتوبيس</h3><button class="modal-close" type="button" onclick="closeDriverModal('create-driver-modal')">×</button></div>
    <form method="POST" action="{{ route('accounting.drivers.store') }}">
      @csrf<input type="hidden" name="_form_type" value="create-driver">
      <div class="modal-body">
        <div class="grid-2">
          <div class="field"><label>اسم السائق *</label><input name="name" required maxlength="100"></div>
          <div class="field"><label>الهاتف</label><input name="phone" maxlength="30"></div>
          <div class="field"><label>البريد الإلكتروني</label><input type="email" name="email" maxlength="150"></div>
          <div class="field"><label>رقم الرخصة</label><input name="license_number" maxlength="100"></div>
          <div class="field"><label>تاريخ الرخصة</label><input type="date" name="LicenseDate"></div>
          <div class="field"><label>درجة الإجازة</label><input name="degreeOfLeave" maxlength="100"></div>
          <div class="field"><label>السيارات والأتوبيسات</label><select name="vehicle_ids[]" multiple size="4">@foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}">{{ $vehicle->name }}</option>@endforeach</select></div>
        </div>
        @if($vehicles->isEmpty())<small class="hint">أضف سيارة أو أتوبيس أولًا لربط السائق به.</small>@endif
      </div>
      <div class="modal-foot"><button class="btn btn-gold" type="submit">حفظ السائق</button><button class="btn btn-dark" type="button" onclick="closeDriverModal('create-driver-modal')">إلغاء</button></div>
    </form>
  </div>
</div>
@endsystemPermission

@systemPermission('drivers', 'update')
<div class="modal-overlay" id="edit-driver-modal" role="dialog" aria-modal="true" aria-labelledby="edit-driver-title">
  <div class="modal wide">
    <div class="modal-head"><h3 id="edit-driver-title">تعديل بيانات السائق</h3><button class="modal-close" type="button" onclick="closeDriverModal('edit-driver-modal')">×</button></div>
    <form id="edit-driver-form" method="POST">
      @csrf @method('PUT')
      <div class="modal-body">
        <div class="grid-2">
          <div class="field"><label>اسم السائق *</label><input id="edit-driver-name" name="name" required maxlength="100"></div>
          <div class="field"><label>الهاتف</label><input id="edit-driver-phone" name="phone" maxlength="30"></div>
          <div class="field"><label>البريد الإلكتروني</label><input id="edit-driver-email" type="email" name="email" maxlength="150"></div>
          <div class="field"><label>رقم الرخصة</label><input id="edit-driver-license" name="license_number" maxlength="100"></div>
          <div class="field"><label>تاريخ الرخصة</label><input id="edit-driver-license-date" type="date" name="LicenseDate"></div>
          <div class="field"><label>درجة الإجازة</label><input id="edit-driver-leave" name="degreeOfLeave" maxlength="100"></div>
          <div class="field"><label>الحالة</label><input id="edit-driver-status" name="status" maxlength="20"></div>
          <div class="field"><label>السيارات والأتوبيسات</label><select id="edit-driver-vehicles" name="vehicle_ids[]" multiple size="4">@foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}">{{ $vehicle->name }}</option>@endforeach</select></div>
        </div>
        @endsystemPermission
      </div>
      <div class="modal-foot"><button class="btn btn-gold" type="submit">حفظ التعديلات</button><button class="btn btn-dark" type="button" onclick="closeDriverModal('edit-driver-modal')">إلغاء</button></div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
function openDriverModal(id) { document.getElementById(id).classList.add('open'); }
function closeDriverModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('[data-edit-driver]').forEach(button => button.addEventListener('click', () => {
  const data = button.dataset;
  const form = document.getElementById('edit-driver-form');
  form.action = data.url;
  document.getElementById('edit-driver-name').value = data.name;
  document.getElementById('edit-driver-phone').value = data.phone || '';
  document.getElementById('edit-driver-email').value = data.email || '';
  document.getElementById('edit-driver-license').value = data.license || '';
  document.getElementById('edit-driver-license-date').value = data.licenseDate || '';
  document.getElementById('edit-driver-leave').value = data.leave || '';
  document.getElementById('edit-driver-status').value = data.status || 'available';
  const vehicleIds = JSON.parse(data.vehicles || '[]').map(String);
  [...document.getElementById('edit-driver-vehicles').options].forEach(option => {
    option.selected = vehicleIds.includes(option.value);
  });
  openDriverModal('edit-driver-modal');
}));
</script>
@endsection
