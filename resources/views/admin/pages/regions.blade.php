@extends('admin.layouts.app')
@section('title')إدارة المناطق@endsection
@section('page_title')إدارة المناطق والفروع@endsection
@section('content')

<div class="panel">
  <div class="panel-head">
    <h3>🧭 ربط الرحلات بالمناطق</h3>
    @systemPermission('trip_hotels', 'view')
    <a class="btn btn-teal btn-sm" href="{{ route('accounting.trip-hotels') }}">🏨 ربط الرحلات بالفنادق</a>
    @endsystemPermission
  </div>
  <div class="panel-body hint">
    الرحلة والفندق يجب أن يكونا في المنطقة نفسها. الرحلات غير المصنفة لا تظهر للموظفين.
  </div>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>الرحلة</th><th>المنطقة</th></tr></thead>
      <tbody id="region-trips-body">
        <tr><td colspan="2" class="empty-state">جارٍ تحميل الرحلات...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<div class="panel">
  <div class="panel-head">
    <h3>🗺️ المناطق والفروع</h3>
    <input id="region-search" type="search" class="search-inline" placeholder="🔍 بحث عن منطقة...">
    <div class="toolbar">
      <button class="btn btn-dark btn-sm" id="toggle-archived" type="button">عرض المعطّل</button>
      @systemPermission('regions', 'create')
      <button class="btn btn-gold" type="button" id="add-region">＋ إضافة منطقة</button>
      @endsystemPermission
    </div>
  </div>
  <div id="regions-message" class="panel-body" hidden></div>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead>
        <tr><th class="num">م</th><th>اسم المنطقة / الفرع</th><th class="num">الحالة</th><th class="num">الارتباطات</th><th class="num">الإجراءات</th></tr>
      </thead>
      <tbody id="regions-body">
        <tr><td colspan="5" class="empty-state">جارٍ تحميل المناطق...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<div class="panel">
  <div class="panel-head">
    <h3>🏨 ربط الفنادق بالمناطق</h3>
  </div>
  <div class="panel-body hint">
    المنطقة المختارة للفندق هي التي تحدد الحجوزات التي تظهر لموظفيها. الفندق غير المصنف لا يظهر في لوحة الموظفين.
  </div>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>الفندق</th><th>المنطقة</th></tr></thead>
      <tbody id="region-hotels-body">
        <tr><td colspan="2" class="empty-state">جارٍ تحميل الفنادق...</td></tr>
      </tbody>
    </table>
  </div>
</div>

@if(\App\Support\SystemPermissions::allows($user, 'regions', 'create') || \App\Support\SystemPermissions::allows($user, 'regions', 'update'))
<div class="modal-overlay" id="region-modal" role="dialog" aria-modal="true" aria-labelledby="region-modal-title">
  <div class="modal">
    <div class="modal-head">
      <h3 id="region-modal-title">إضافة منطقة جديدة</h3>
      <button class="modal-close" type="button" data-close-modal aria-label="إغلاق">×</button>
    </div>
    @endif
    <form id="region-form">
      <div class="modal-body">
        <input type="hidden" id="region-id">
        <div class="field">
          <label for="region-name">اسم المنطقة / الفرع *</label>
          <input id="region-name" type="text" maxlength="120" required placeholder="مثال: المنطقة الغربية">
        </div>
        <small class="hint">تعديل الاسم يحدّث اسم المنطقة في القيود المرتبطة بها.</small>
      </div>
      <div class="modal-foot">
        <button class="btn btn-gold" type="submit">حفظ</button>
        <button class="btn btn-dark" type="button" data-close-modal>إلغاء</button>
      </div>
    </form>
  </div>
</div>

@endsection
@section('scripts')
<script>
let REGIONS = [];
let HOTELS = [];
let TRIPS = [];
let HOTEL_REGIONS = [];
let TRIP_REGIONS = [];
let showInactiveRegions = false;
const CAN_CREATE_REGIONS = @json(\App\Support\SystemPermissions::allows($user, 'regions', 'create'));
const CAN_UPDATE_REGIONS = @json(\App\Support\SystemPermissions::allows($user, 'regions', 'update'));
const CAN_DELETE_REGIONS = @json(\App\Support\SystemPermissions::allows($user, 'regions', 'delete'));

async function loadRegions() {
  const body = document.getElementById('regions-body');
  body.innerHTML = '<tr><td colspan="5" class="empty-state">جارٍ تحميل المناطق...</td></tr>';
  try {
    const data = await api('/api/regions');
    REGIONS = data.regions || [];
    renderRegions();
  } catch (error) {
    body.innerHTML = '<tr><td colspan="5" class="empty-state">تعذر تحميل المناطق.</td></tr>';
    toast(error.message, 'err');
  }
}

async function loadRegionHotels() {
  const body = document.getElementById('region-hotels-body');
  try {
    const data = await api('/api/region-hotels');
    HOTELS = data.hotels || [];
    HOTEL_REGIONS = data.regions || [];
    renderRegionHotels();
  } catch (error) {
    body.innerHTML = '<tr><td colspan="2" class="empty-state">تعذر تحميل الفنادق.</td></tr>';
    toast(error.message, 'err');
  }
}

async function loadRegionTrips() {
  const body = document.getElementById('region-trips-body');
  try {
    const data = await api('/api/region-trips');
    TRIPS = data.trips || [];
    TRIP_REGIONS = data.regions || [];
    renderRegionTrips();
  } catch (error) {
    body.innerHTML = '<tr><td colspan="2" class="empty-state">تعذر تحميل الرحلات.</td></tr>';
    toast(error.message, 'err');
  }
}

function renderRegionTrips() {
  const body = document.getElementById('region-trips-body');
  if (!TRIPS.length) {
    body.innerHTML = '<tr><td colspan="2" class="empty-state">لا توجد رحلات.</td></tr>';
    return;
  }

  body.innerHTML = TRIPS.map(trip => {
    const currentRegionId = trip.region_id ? Number(trip.region_id) : null;
    const options = TRIP_REGIONS.map(region =>
      `<option value="${Number(region.id)}" ${currentRegionId === Number(region.id) ? 'selected' : ''}>${esc(region.name)}${region.is_active ? '' : ' (معطلة)'}</option>`
    ).join('');

    return `<tr>
      <td>${esc(trip.name)}</td>
      <td>
        <select class="trip-region-select" data-trip-id="${Number(trip.id)}" ${CAN_UPDATE_REGIONS ? '' : 'disabled'}>
          <option value="">غير مصنفة</option>
          ${options}
        </select>
        ${trip.region_id && !trip.region_is_active ? '<span class="badge draft">منطقة معطّلة</span>' : ''}
      </td>
    </tr>`;
  }).join('');
}

function renderRegionHotels() {
  const body = document.getElementById('region-hotels-body');
  if (!HOTELS.length) {
    body.innerHTML = '<tr><td colspan="2" class="empty-state">لا توجد فنادق.</td></tr>';
    return;
  }

  body.innerHTML = HOTELS.map(hotel => {
    const currentRegionId = hotel.region_id ? Number(hotel.region_id) : null;
    const options = HOTEL_REGIONS.map(region =>
      `<option value="${Number(region.id)}" ${currentRegionId === Number(region.id) ? 'selected' : ''}>${esc(region.name)}${region.is_active ? '' : ' (معطلة)'}</option>`
    ).join('');

    return `<tr>
      <td>${esc(hotel.name)}</td>
      <td>
        <select class="hotel-region-select" data-hotel-id="${Number(hotel.id)}" ${CAN_UPDATE_REGIONS ? '' : 'disabled'}>
          <option value="">غير مصنف</option>
          ${options}
        </select>
        ${hotel.region_id && !hotel.region_is_active ? '<span class="badge draft">منطقة معطّلة</span>' : ''}
      </td>
    </tr>`;
  }).join('');
}

function renderRegions() {
  const body = document.getElementById('regions-body');
  const query = document.getElementById('region-search').value.trim().toLowerCase();
  const regions = REGIONS.filter(region =>
    (showInactiveRegions || region.is_active) &&
    (!query || region.name.toLowerCase().includes(query))
  );

  document.getElementById('toggle-archived').textContent = showInactiveRegions ? 'إخفاء المعطّل' : 'عرض المعطّل';
  if (!regions.length) {
    body.innerHTML = `<tr><td colspan="5" class="empty-state">${query ? 'لا توجد نتائج مطابقة.' : 'لا توجد مناطق لعرضها.'}</td></tr>`;
    return;
  }

  body.innerHTML = regions.map(region => `
    <tr>
      <td class="num">${Number(region.id)}</td>
      <td>${esc(region.name)}</td>
      <td class="num">${region.is_active ? '<span class="badge posted">نشطة</span>' : '<span class="badge draft">معطّلة</span>'}</td>
      <td class="num">${Number(region.usage_count) || '—'}</td>
      <td class="num">
        <div class="j-actions">
          ${CAN_UPDATE_REGIONS ? `<button class="btn btn-sm btn-dark" type="button" data-region-action="edit" data-id="${Number(region.id)}">تعديل</button>` : ''}
          ${CAN_UPDATE_REGIONS && region.is_active
            ? `<button class="btn btn-sm btn-gold" type="button" data-region-action="deactivate" data-id="${Number(region.id)}">تعطيل</button>`
            : CAN_UPDATE_REGIONS ? `<button class="btn btn-sm btn-teal" type="button" data-region-action="activate" data-id="${Number(region.id)}">تفعيل</button>` : ''}
          ${!CAN_DELETE_REGIONS
            ? ''
            : Number(region.usage_count) > 0
            ? '<button class="btn btn-sm btn-dark" type="button" disabled title="لا يمكن حذف منطقة مرتبطة ببيانات">حذف</button>'
            : `<button class="btn btn-sm btn-red" type="button" data-region-action="delete" data-id="${Number(region.id)}">حذف</button>`}
        </div>
      </td>
    </tr>
  `).join('');
}

function openRegionModal(region = null) {
  if (region ? !CAN_UPDATE_REGIONS : !CAN_CREATE_REGIONS) return;
  document.getElementById('region-modal-title').textContent = region ? 'تعديل المنطقة' : 'إضافة منطقة جديدة';
  document.getElementById('region-id').value = region?.id || '';
  document.getElementById('region-name').value = region?.name || '';
  openModal('region-modal');
  document.getElementById('region-name').focus();
}

document.getElementById('add-region')?.addEventListener('click', () => openRegionModal());
document.getElementById('toggle-archived').addEventListener('click', () => {
  showInactiveRegions = !showInactiveRegions;
  renderRegions();
});
document.getElementById('region-search').addEventListener('input', renderRegions);
document.getElementById('region-trips-body').addEventListener('change', async event => {
  const select = event.target.closest('.trip-region-select');
  if (!select) return;

  const trip = TRIPS.find(item => Number(item.id) === Number(select.dataset.tripId));
  if (!trip) return toast('الرحلة غير موجودة، أعد تحميل الصفحة.', 'err');

  const regionId = select.value ? Number(select.value) : null;
  try {
    await api(`/api/trips/${trip.id}/region`, { method: 'PUT', body: { region_id: regionId } });
    toast('تم تحديث منطقة الرحلة');
    await Promise.all([loadRegionTrips(), loadRegions()]);
  } catch (error) {
    toast(error.message, 'err');
    await loadRegionTrips();
  }
});
document.getElementById('region-hotels-body').addEventListener('change', async event => {
  const select = event.target.closest('.hotel-region-select');
  if (!select) return;

  const hotel = HOTELS.find(item => Number(item.id) === Number(select.dataset.hotelId));
  if (!hotel) return toast('الفندق غير موجود، أعد تحميل الصفحة.', 'err');

  const regionId = select.value ? Number(select.value) : null;
  try {
    await api(`/api/hotels/${hotel.id}/region`, { method: 'PUT', body: { region_id: regionId } });
    toast('تم تحديث منطقة الفندق');
    await Promise.all([loadRegionHotels(), loadRegions()]);
  } catch (error) {
    toast(error.message, 'err');
    await loadRegionHotels();
  }
});
document.getElementById('regions-body').addEventListener('click', async event => {
  const button = event.target.closest('[data-region-action]');
  if (!button) return;

  const region = REGIONS.find(item => Number(item.id) === Number(button.dataset.id));
  if (!region) return toast('المنطقة غير موجودة، أعد تحميل الصفحة.', 'err');

  if (button.dataset.regionAction === 'edit') return openRegionModal(region);
  if (button.dataset.regionAction === 'deactivate' || button.dataset.regionAction === 'activate') {
    const action = button.dataset.regionAction;
    try {
      await api(`/api/regions/${region.id}/${action}`, { method: 'POST' });
      toast(action === 'deactivate' ? 'تم تعطيل المنطقة' : 'تم تفعيل المنطقة');
      await Promise.all([loadRegions(), loadRegionHotels(), loadRegionTrips()]);
    } catch (error) {
      toast(error.message, 'err');
    }
    return;
  }

  if (!await confirmDlg(`حذف المنطقة «${region.name}» نهائيًا؟`)) return;
  try {
    await api(`/api/regions/${region.id}`, { method: 'DELETE' });
    toast('تم حذف المنطقة');
    await Promise.all([loadRegions(), loadRegionHotels(), loadRegionTrips()]);
  } catch (error) {
    toast(error.message, 'err');
  }
});

document.getElementById('region-form').addEventListener('submit', async event => {
  event.preventDefault();
  const id = document.getElementById('region-id').value;
  const name = document.getElementById('region-name').value.trim();
  if (!name) return toast('اكتب اسم المنطقة', 'err');

  try {
    await api(id ? `/api/regions/${id}` : '/api/regions', {
      method: id ? 'PUT' : 'POST',
      body: { name },
    });
    closeModal('region-modal');
    toast(id ? 'تم تعديل المنطقة' : 'تمت إضافة المنطقة');
    await Promise.all([loadRegions(), loadRegionHotels(), loadRegionTrips()]);
  } catch (error) {
    toast(error.message, 'err');
  }
});

loadRegions();
loadRegionHotels();
loadRegionTrips();
</script>
@endsection
