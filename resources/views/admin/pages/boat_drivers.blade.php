@extends('admin.layouts.app')
@section('title')سائقو المراكب@endsection
@section('page_title')سائقو المراكب@endsection
@section('content')

@if(session('status'))<div class="alert ok">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
@php($visibleBoatDrivers = request()->boolean('include_archived') ? $boatDrivers : $boatDrivers->where('is_active', true))

<div class="panel">
  <div class="panel-head">
    <h3>سائقو المراكب — {{ $visibleBoatDrivers->count() }}</h3>
    @if(request()->boolean('include_archived'))
      <a class="btn btn-dark" href="{{ route('accounting.boat-drivers') }}">إخفاء الموقوف</a>
    @else
      <a class="btn btn-dark" href="{{ route('accounting.boat-drivers', ['include_archived' => 1]) }}">عرض الموقوف</a>
    @endif
    <button class="btn btn-gold" type="button" onclick="openBoatDriverModal('create-boat-driver-modal')">＋ إضافة سائق مركب</button>
  </div>
  <div class="panel-body hint">اربط كل سائق بمركب نشط ليظهر ضمن خيارات التشغيل اليومية.</div>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>السائق</th><th>المركب</th><th>الهاتف</th><th>رقم الرخصة</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
    <tbody>
      @forelse($visibleBoatDrivers as $driver)
        <tr>
          <td>{{ $driver->name }}</td><td>{{ $driver->boat?->name ?? 'مركب غير نشط' }}</td><td>{{ $driver->phone ?: '—' }}</td>
          <td>{{ $driver->license_number ?: '—' }}</td><td>{{ $driver->is_active ? 'نشط' : 'موقوف' }}</td>
          <td><div class="j-actions">
            @if($driver->is_active)
              <button class="btn btn-dark btn-sm" type="button" data-edit-boat-driver
                data-url="{{ route('accounting.boat-drivers.update', $driver->id) }}"
                data-name="{{ $driver->name }}" data-phone="{{ $driver->phone }}"
                data-license="{{ $driver->license_number }}" data-boat="{{ $driver->boat_id }}">تعديل</button>
              <form method="POST" action="{{ route('accounting.boat-drivers.destroy', $driver->id) }}" onsubmit="return confirm('سيتم إيقاف سائق المركب. متابعة؟')">
                @csrf @method('DELETE')<button class="btn btn-red btn-sm" type="submit">إيقاف</button>
              </form>
            @else
              <form method="POST" action="{{ route('accounting.boat-drivers.restore', $driver->id) }}">@csrf @method('PUT')<button class="btn btn-gold btn-sm" type="submit">تفعيل</button></form>
            @endif
          </div></td>
        </tr>
      @empty<tr><td colspan="6" class="empty-state">لا يوجد سائقو مراكب.</td></tr>@endforelse
    </tbody>
  </table></div>
</div>

<div class="modal-overlay" id="create-boat-driver-modal" role="dialog" aria-modal="true" aria-labelledby="create-boat-driver-title">
  <div class="modal">
    <div class="modal-head"><h3 id="create-boat-driver-title">إضافة سائق مركب</h3><button class="modal-close" type="button" onclick="closeBoatDriverModal('create-boat-driver-modal')">×</button></div>
    <form method="POST" action="{{ route('accounting.boat-drivers.store') }}">
      @csrf
      <div class="modal-body">
        <div class="field"><label>اسم السائق *</label><input name="name" required maxlength="255"></div>
        <div class="field"><label>المركب *</label><select name="boat_id" required><option value="">اختر المركب</option>@foreach($boats as $boat)<option value="{{ $boat->id }}">{{ $boat->name }}</option>@endforeach</select></div>
        <div class="field"><label>الهاتف</label><input name="phone" maxlength="255"></div>
        <div class="field"><label>رقم الرخصة</label><input name="license_number" maxlength="255"></div>
        @if($boats->isEmpty())<small class="hint">أضف مركبًا نشطًا أولًا.</small>@endif
      </div>
      <div class="modal-foot"><button class="btn btn-gold" type="submit" @disabled($boats->isEmpty())>حفظ السائق</button><button class="btn btn-dark" type="button" onclick="closeBoatDriverModal('create-boat-driver-modal')">إلغاء</button></div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="edit-boat-driver-modal" role="dialog" aria-modal="true" aria-labelledby="edit-boat-driver-title">
  <div class="modal">
    <div class="modal-head"><h3 id="edit-boat-driver-title">تعديل سائق المركب</h3><button class="modal-close" type="button" onclick="closeBoatDriverModal('edit-boat-driver-modal')">×</button></div>
    <form id="edit-boat-driver-form" method="POST">
      @csrf @method('PUT')
      <div class="modal-body">
        <div class="field"><label>اسم السائق *</label><input id="edit-boat-driver-name" name="name" required maxlength="255"></div>
        <div class="field"><label>المركب *</label><select id="edit-boat-driver-boat" name="boat_id" required><option value="">اختر المركب</option>@foreach($boats as $boat)<option value="{{ $boat->id }}">{{ $boat->name }}</option>@endforeach</select></div>
        <div class="field"><label>الهاتف</label><input id="edit-boat-driver-phone" name="phone" maxlength="255"></div>
        <div class="field"><label>رقم الرخصة</label><input id="edit-boat-driver-license" name="license_number" maxlength="255"></div>
      </div>
      <div class="modal-foot"><button class="btn btn-gold" type="submit">حفظ التعديلات</button><button class="btn btn-dark" type="button" onclick="closeBoatDriverModal('edit-boat-driver-modal')">إلغاء</button></div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
function openBoatDriverModal(id) { document.getElementById(id).classList.add('open'); }
function closeBoatDriverModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('[data-edit-boat-driver]').forEach(button => button.addEventListener('click', () => {
  const data = button.dataset;
  const form = document.getElementById('edit-boat-driver-form');
  form.action = data.url;
  document.getElementById('edit-boat-driver-name').value = data.name;
  document.getElementById('edit-boat-driver-phone').value = data.phone || '';
  document.getElementById('edit-boat-driver-license').value = data.license || '';
  document.getElementById('edit-boat-driver-boat').value = data.boat;
  openBoatDriverModal('edit-boat-driver-modal');
}));
</script>
@endsection
