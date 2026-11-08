@extends('admin.layouts.app')
@section('title')إدارة الفنادق@endsection
@section('page_title')إدارة الفنادق@endsection
@section('content')

@if(session('status'))<div class="alert ok">{{ session('status') }}</div>@endif

<div class="panel">
  <div class="panel-head">
    <h3>الفنادق — {{ $hotels->count() }}</h3>
    <a class="btn btn-dark" href="{{ route('accounting.trips') }}">إدارة الرحلات</a>
    @if(request()->boolean('include_archived'))
      <a class="btn btn-dark" href="{{ route('accounting.hotels') }}">عرض النشط فقط</a>
    @else
      <a class="btn btn-dark" href="{{ route('accounting.hotels', ['include_archived' => 1]) }}">عرض المؤرشف</a>
    @endif
    @systemPermission('hotels', 'create')
    <button class="btn btn-gold" type="button" onclick="openHotelModal('create-hotel-modal')">＋ إضافة فندق</button>
    @endsystemPermission
  </div>
  <div class="panel-body hint">الفنادق خاصة بشركتك. اربط الفندق بمنطقة نشطة ليظهر لموظفي المنطقة، واستخدم صفحة <a href="{{ route('accounting.trip-hotels') }}">ربط الرحلات بالفنادق</a> لإتاحة الفنادق للرحلات.</div>
  <div class="panel-head"><input id="hotel-search" type="search" class="search-inline" placeholder="🔍 بحث بالاسم أو المنطقة..."></div>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>اسم الفندق</th><th>المنطقة</th><th>الهاتف</th><th>البريد الإلكتروني</th><th>الرحلات المرتبطة</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
    <tbody>
      @forelse($hotels as $hotel)
        <tr data-hotel-row data-search="{{ mb_strtolower($hotel->name.' '.($hotel->region?->name ?? '')) }}">
          <td>{{ $hotel->name }}</td><td>{{ $hotel->region?->name ?? 'غير محددة' }}</td>
          <td dir="auto">{{ $hotel->phone ?: '—' }}</td><td dir="auto">{{ $hotel->email ?: '—' }}</td>
          <td>{{ $hotel->trip_hotels_count }}</td><td>{{ $hotel->status ? 'نشط' : 'مؤرشف' }}</td>
          <td><div class="j-actions">
            @systemPermission('hotels', 'update')
            <button class="btn btn-dark btn-sm" type="button" data-edit-hotel
              data-id="{{ $hotel->id }}" data-url="{{ route('accounting.hotels.update', $hotel) }}"
              data-name="{{ $hotel->name }}" data-region="{{ $hotel->region_id }}"
              data-address="{{ $hotel->address }}" data-phone="{{ $hotel->phone }}"
              data-email="{{ $hotel->email }}" data-status="{{ $hotel->status ? '1' : '0' }}">تعديل</button>
            @endsystemPermission
            @if($hotel->status)
              @systemPermission('hotels', 'delete')
              <form method="POST" action="{{ route('accounting.hotels.destroy', $hotel) }}" onsubmit="return confirm('سيتم أرشفة الفندق مع الاحتفاظ بالحجوزات السابقة. متابعة؟')">
                @csrf @method('DELETE')<button class="btn btn-red btn-sm" type="submit">أرشفة</button>
              </form>
              @endsystemPermission
            @endif
          </div></td>
        </tr>
      @empty<tr><td colspan="7" class="empty-state">لا توجد فنادق {{ request()->boolean('include_archived') ? 'مسجلة' : 'نشطة' }}.</td></tr>@endforelse
    </tbody>
  </table></div>
</div>

<div class="modal-overlay" id="create-hotel-modal" role="dialog" aria-modal="true">
  <div class="modal">
    <div class="modal-head"><h3>إضافة فندق</h3><button class="modal-close" type="button" onclick="closeHotelModal('create-hotel-modal')">×</button></div>
    <form method="POST" action="{{ route('accounting.hotels.store') }}">
      @csrf<input type="hidden" name="_form_type" value="create-hotel">
      <div class="modal-body">
        @if($errors->any() && old('_form_type') === 'create-hotel')<div class="alert err">{{ $errors->first() }}</div>@endif
        <div class="field"><label>اسم الفندق *</label><input name="name" required maxlength="255" value="{{ old('_form_type') === 'create-hotel' ? old('name') : '' }}">@if(old('_form_type') === 'create-hotel') @error('name')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror @endif</div>
        <div class="field"><label>المنطقة</label><select name="region_id"><option value="">بدون منطقة</option>@foreach($regions as $region)<option value="{{ $region->id }}" @selected(old('_form_type') === 'create-hotel' && old('region_id') == $region->id)>{{ $region->name }}{{ $region->is_active ? '' : ' (غير نشطة)' }}</option>@endforeach</select></div>
        <div class="grid-2">
          <div class="field"><label>الهاتف</label><input name="phone" maxlength="50" dir="auto" value="{{ old('_form_type') === 'create-hotel' ? old('phone') : '' }}">@if(old('_form_type') === 'create-hotel') @error('phone')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror @endif</div>
          <div class="field"><label>البريد الإلكتروني</label><input type="email" name="email" maxlength="255" dir="auto" value="{{ old('_form_type') === 'create-hotel' ? old('email') : '' }}">@if(old('_form_type') === 'create-hotel') @error('email')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror @endif</div>
        </div>
        <div class="field"><label>العنوان</label><textarea name="address" rows="3" maxlength="2000">{{ old('_form_type') === 'create-hotel' ? old('address') : '' }}</textarea></div>
      </div>
      <div class="modal-foot"><button class="btn btn-gold" type="submit">حفظ الفندق</button><button class="btn btn-dark" type="button" onclick="closeHotelModal('create-hotel-modal')">إلغاء</button></div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="edit-hotel-modal" role="dialog" aria-modal="true">
  <div class="modal">
    <div class="modal-head"><h3>تعديل الفندق</h3><button class="modal-close" type="button" onclick="closeHotelModal('edit-hotel-modal')">×</button></div>
    <form id="edit-hotel-form" method="POST">
      @csrf @method('PUT')<input type="hidden" name="_form_type" value="edit-hotel"><input type="hidden" name="_resource_id" id="edit-hotel-id">
      <input type="hidden" name="_include_archived" value="{{ request()->boolean('include_archived') ? 1 : 0 }}">
      <div class="modal-body">
        @if($errors->any() && old('_form_type') === 'edit-hotel')<div class="alert err">{{ $errors->first() }}</div>@endif
        <div class="field"><label>اسم الفندق *</label><input id="edit-hotel-name" name="name" required maxlength="255"></div>
        <div class="field"><label>المنطقة</label><select id="edit-hotel-region" name="region_id"><option value="">بدون منطقة</option>@foreach($regions as $region)<option value="{{ $region->id }}">{{ $region->name }}{{ $region->is_active ? '' : ' (غير نشطة)' }}</option>@endforeach</select></div>
        <div class="grid-2"><div class="field"><label>الهاتف</label><input id="edit-hotel-phone" name="phone" maxlength="50" dir="auto"></div><div class="field"><label>البريد الإلكتروني</label><input id="edit-hotel-email" type="email" name="email" maxlength="255" dir="auto"></div></div>
        <div class="field"><label>العنوان</label><textarea id="edit-hotel-address" name="address" rows="3" maxlength="2000"></textarea></div>
        <div class="field"><label>الحالة</label><select id="edit-hotel-status" name="status"><option value="1">نشط</option><option value="0">مؤرشف</option></select></div>
      </div>
      <div class="modal-foot"><button class="btn btn-gold" type="submit">حفظ التعديلات</button><button class="btn btn-dark" type="button" onclick="closeHotelModal('edit-hotel-modal')">إلغاء</button></div>
    </form>
  </div>
</div>

@endsection

@section('scripts')
<script>
function openHotelModal(id) { document.getElementById(id).classList.add('open'); }
function closeHotelModal(id) { document.getElementById(id).classList.remove('open'); }

document.getElementById('hotel-search').addEventListener('input', event => {
  const query = event.target.value.trim().toLowerCase();
  document.querySelectorAll('[data-hotel-row]').forEach(row => {
    row.hidden = query !== '' && !row.dataset.search.includes(query);
  });
});

document.querySelectorAll('[data-edit-hotel]').forEach(button => button.addEventListener('click', () => {
  const data = button.dataset;
  const form = document.getElementById('edit-hotel-form');
  form.action = data.url;
  document.getElementById('edit-hotel-id').value = data.id;
  document.getElementById('edit-hotel-name').value = data.name;
  document.getElementById('edit-hotel-region').value = data.region || '';
  document.getElementById('edit-hotel-phone').value = data.phone || '';
  document.getElementById('edit-hotel-email').value = data.email || '';
  document.getElementById('edit-hotel-address').value = data.address || '';
  document.getElementById('edit-hotel-status').value = data.status;
  openHotelModal('edit-hotel-modal');
}));

@if($errors->any() && old('_form_type') === 'create-hotel')
openHotelModal('create-hotel-modal');
@elseif($errors->any() && old('_form_type') === 'edit-hotel')
{
  const resourceId = @json(old('_resource_id'));
  const button = [...document.querySelectorAll('[data-edit-hotel]')].find(item => item.dataset.id === String(resourceId));
  if (button) button.click();
  @foreach(['name', 'region_id', 'phone', 'email', 'address', 'status'] as $field)
  const field{{ $loop->index }} = document.querySelector(`#edit-hotel-form [name="{{ $field }}"]`);
  if (field{{ $loop->index }}) field{{ $loop->index }}.value = @json(old($field));
  @endforeach
}
@endif
</script>
@endsection
