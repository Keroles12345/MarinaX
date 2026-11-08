@extends('admin.layouts.app')
@section('title')إدارة الرحلات@endsection
@section('page_title')إدارة الرحلات@endsection
@section('content')

@if(session('status'))<div class="alert ok">{{ session('status') }}</div>@endif

<div class="panel">
  <div class="panel-head">
    <h3>الرحلات — {{ $trips->count() }}</h3>
    <a class="btn btn-dark" href="{{ route('accounting.hotels') }}">إدارة الفنادق</a>
    <a class="btn btn-dark" href="{{ route('accounting.trip-hotels') }}">ربط الرحلات بالفنادق</a>
    @if(request()->boolean('include_archived'))
      <a class="btn btn-dark" href="{{ route('accounting.trips') }}">عرض النشط فقط</a>
    @else
      <a class="btn btn-dark" href="{{ route('accounting.trips', ['include_archived' => 1]) }}">عرض المؤرشف</a>
    @endif
    @systemPermission('trips', 'create')
    <button class="btn btn-gold" type="button" onclick="openTripModal('create-trip-modal')">＋ إضافة رحلة</button>
    @endsystemPermission
  </div>
  <div class="panel-body hint">الرحلات خاصة بشركتك. اربط الرحلة بمنطقة نشطة لتظهر لموظفي المنطقة.</div>
  <div class="panel-head"><input id="trip-search" type="search" class="search-inline" placeholder="🔍 بحث بالاسم أو المنطقة..."></div>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>اسم الرحلة</th><th>المنطقة</th><th>ميعاد الرحلة</th><th>الفنادق المرتبطة</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
    <tbody>
      @forelse($trips as $trip)
        <tr data-trip-row data-search="{{ mb_strtolower($trip->name.' '.($trip->region?->name ?? '')) }}">
          <td>{{ $trip->name }}</td><td>{{ $trip->region?->name ?? 'غير محددة' }}</td>
          <td>{{ $trip->time_trip ? substr((string) $trip->time_trip, 0, 5) : '—' }}</td>
          <td>{{ $trip->active_hotels_count }}</td><td>{{ $trip->status ? 'نشطة' : 'مؤرشفة' }}</td>
          <td><div class="j-actions">
            @systemPermission('trips', 'update')
            <button class="btn btn-dark btn-sm" type="button" data-edit-trip
              data-id="{{ $trip->id }}" data-url="{{ route('accounting.trips.update', $trip) }}"
              data-name="{{ $trip->name }}" data-region="{{ $trip->region_id }}"
              data-time="{{ $trip->time_trip ? substr((string) $trip->time_trip, 0, 5) : '' }}"
              data-description="{{ $trip->description }}" data-status="{{ $trip->status ? '1' : '0' }}">تعديل</button>
            @endsystemPermission
            @if($trip->status)
              @systemPermission('trips', 'delete')
              <form method="POST" action="{{ route('accounting.trips.destroy', $trip) }}" onsubmit="return confirm('سيتم أرشفة الرحلة مع الاحتفاظ بالحجوزات السابقة. متابعة؟')">
                @csrf @method('DELETE')<button class="btn btn-red btn-sm" type="submit">أرشفة</button>
              </form>
              @endsystemPermission
            @endif
          </div></td>
        </tr>
      @empty<tr><td colspan="6" class="empty-state">لا توجد رحلات {{ request()->boolean('include_archived') ? 'مسجلة' : 'نشطة' }}.</td></tr>@endforelse
    </tbody>
  </table></div>
</div>

<div class="modal-overlay" id="create-trip-modal" role="dialog" aria-modal="true">
  <div class="modal">
    <div class="modal-head"><h3>إضافة رحلة</h3><button class="modal-close" type="button" onclick="closeTripModal('create-trip-modal')">×</button></div>
    <form method="POST" action="{{ route('accounting.trips.store') }}">
      @csrf<input type="hidden" name="_form_type" value="create-trip">
      <div class="modal-body">
        @if($errors->any() && old('_form_type') === 'create-trip')<div class="alert err">{{ $errors->first() }}</div>@endif
        <div class="field"><label>اسم الرحلة *</label><input name="name" required maxlength="255" value="{{ old('_form_type') === 'create-trip' ? old('name') : '' }}">@if(old('_form_type') === 'create-trip') @error('name')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror @endif</div>
        <div class="grid-2">
          <div class="field"><label>المنطقة</label><select name="region_id"><option value="">بدون منطقة</option>@foreach($regions as $region)<option value="{{ $region->id }}" @selected(old('_form_type') === 'create-trip' && old('region_id') == $region->id)>{{ $region->name }}{{ $region->is_active ? '' : ' (غير نشطة)' }}</option>@endforeach</select></div>
          <div class="field"><label>ميعاد الرحلة</label><input type="time" name="time_trip" value="{{ old('_form_type') === 'create-trip' ? old('time_trip') : '' }}"></div>
        </div>
        <div class="field"><label>الوصف</label><textarea name="description" rows="3" maxlength="4000">{{ old('_form_type') === 'create-trip' ? old('description') : '' }}</textarea></div>
      </div>
      <div class="modal-foot"><button class="btn btn-gold" type="submit">حفظ الرحلة</button><button class="btn btn-dark" type="button" onclick="closeTripModal('create-trip-modal')">إلغاء</button></div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="edit-trip-modal" role="dialog" aria-modal="true">
  <div class="modal">
    <div class="modal-head"><h3>تعديل الرحلة</h3><button class="modal-close" type="button" onclick="closeTripModal('edit-trip-modal')">×</button></div>
    <form id="edit-trip-form" method="POST">
      @csrf @method('PUT')<input type="hidden" name="_form_type" value="edit-trip"><input type="hidden" name="_resource_id" id="edit-trip-id">
      <input type="hidden" name="_include_archived" value="{{ request()->boolean('include_archived') ? 1 : 0 }}">
      <div class="modal-body">
        @if($errors->any() && old('_form_type') === 'edit-trip')<div class="alert err">{{ $errors->first() }}</div>@endif
        <div class="field"><label>اسم الرحلة *</label><input id="edit-trip-name" name="name" required maxlength="255"></div>
        <div class="grid-2">
          <div class="field"><label>المنطقة</label><select id="edit-trip-region" name="region_id"><option value="">بدون منطقة</option>@foreach($regions as $region)<option value="{{ $region->id }}">{{ $region->name }}{{ $region->is_active ? '' : ' (غير نشطة)' }}</option>@endforeach</select></div>
          <div class="field"><label>ميعاد الرحلة</label><input id="edit-trip-time" type="time" name="time_trip"></div>
        </div>
        <div class="field"><label>الوصف</label><textarea id="edit-trip-description" name="description" rows="3" maxlength="4000"></textarea></div>
        <div class="field"><label>الحالة</label><select id="edit-trip-status" name="status"><option value="1">نشطة</option><option value="0">مؤرشفة</option></select></div>
      </div>
      <div class="modal-foot"><button class="btn btn-gold" type="submit">حفظ التعديلات</button><button class="btn btn-dark" type="button" onclick="closeTripModal('edit-trip-modal')">إلغاء</button></div>
    </form>
  </div>
</div>

@endsection

@section('scripts')
<script>
function openTripModal(id) { document.getElementById(id).classList.add('open'); }
function closeTripModal(id) { document.getElementById(id).classList.remove('open'); }

document.getElementById('trip-search').addEventListener('input', event => {
  const query = event.target.value.trim().toLowerCase();
  document.querySelectorAll('[data-trip-row]').forEach(row => {
    row.hidden = query !== '' && !row.dataset.search.includes(query);
  });
});

document.querySelectorAll('[data-edit-trip]').forEach(button => button.addEventListener('click', () => {
  const data = button.dataset;
  const form = document.getElementById('edit-trip-form');
  form.action = data.url;
  document.getElementById('edit-trip-id').value = data.id;
  document.getElementById('edit-trip-name').value = data.name;
  document.getElementById('edit-trip-region').value = data.region || '';
  document.getElementById('edit-trip-time').value = data.time || '';
  document.getElementById('edit-trip-description').value = data.description || '';
  document.getElementById('edit-trip-status').value = data.status;
  openTripModal('edit-trip-modal');
}));

@if($errors->any() && old('_form_type') === 'create-trip')
openTripModal('create-trip-modal');
@elseif($errors->any() && old('_form_type') === 'edit-trip')
{
  const resourceId = @json(old('_resource_id'));
  const button = [...document.querySelectorAll('[data-edit-trip]')].find(item => item.dataset.id === String(resourceId));
  if (button) button.click();
  @foreach(['name', 'region_id', 'time_trip', 'description', 'status'] as $field)
  const field{{ $loop->index }} = document.querySelector(`#edit-trip-form [name="{{ $field }}"]`);
  if (field{{ $loop->index }}) field{{ $loop->index }}.value = @json(old($field));
  @endforeach
}
@endif
</script>
@endsection
