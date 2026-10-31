@extends('admin.layouts.app')
@section('title')ربط الرحلات بالفنادق@endsection
@section('page_title')ربط الرحلات بالفنادق@endsection
@section('content')
<div class="panel">
  <div class="panel-head">
    <h3>🏨 اختيار فنادق كل رحلة</h3>
    <input id="trip-search" type="search" class="search-inline" placeholder="🔍 ابحث عن رحلة...">
  </div>
  <div class="panel-body hint">
    اختر فندقًا واحدًا أو أكثر لكل رحلة، وحدد وقت الاستلام لكل فندق على حدة. تظهر فقط الفنادق النشطة التابعة لنفس منطقة الرحلة.
    إلغاء الاختيار يعطّل الربط للعمليات الجديدة ويحافظ على الحجوزات القديمة.
  </div>
  <div id="trip-hotels-message" class="panel-body" hidden></div>
  <div id="trip-hotels-list" class="panel-body">
    <div class="empty-state">جارٍ تحميل الرحلات والفنادق...</div>
  </div>
</div>
@endsection
@section('scripts')
<script>
let TRIP_HOTEL_DATA = { trips: [], hotels: [], assignments: [] };

async function loadTripHotelData() {
  const list = document.getElementById('trip-hotels-list');
  list.innerHTML = '<div class="empty-state">جارٍ تحميل الرحلات والفنادق...</div>';

  try {
    TRIP_HOTEL_DATA = await api('/api/trip-hotels');
    renderTripHotelList();
  } catch (error) {
    list.innerHTML = '<div class="empty-state">تعذر تحميل الرحلات والفنادق.</div>';
    toast(error.message, 'err');
  }
}

function renderTripHotelList() {
  const list = document.getElementById('trip-hotels-list');
  const query = document.getElementById('trip-search').value.trim().toLowerCase();
  const trips = (TRIP_HOTEL_DATA.trips || []).filter(trip =>
    !query || trip.name.toLowerCase().includes(query) || (trip.region_name || '').toLowerCase().includes(query)
  );

  if (!trips.length) {
    list.innerHTML = '<div class="empty-state">لا توجد رحلات مطابقة.</div>';
    return;
  }

  list.innerHTML = trips.map(trip => {
    const hotels = (TRIP_HOTEL_DATA.hotels || []).filter(hotel =>
      Number(hotel.region_id) === Number(trip.region_id)
    );
    const assignmentByHotel = new Map((TRIP_HOTEL_DATA.assignments || [])
      .filter(assignment => Number(assignment.trip_id) === Number(trip.id))
      .map(assignment => [Number(assignment.hotel_id), assignment]));

    return `<section class="panel" style="margin-bottom:1rem">
      <div class="panel-head">
        <div>
          <h3>${esc(trip.name)}</h3>
          <small class="hint">المنطقة: ${esc(trip.region_name || 'غير محددة')}</small>
        </div>
        <button type="button" class="btn btn-gold" data-save-trip="${Number(trip.id)}">حفظ الفنادق</button>
      </div>
      <div class="panel-body">
        ${!trip.region_id || !trip.region_is_active
          ? '<p class="hint">اربط الرحلة بمنطقة نشطة أولًا من صفحة المناطق.</p>'
          : !hotels.length
            ? '<p class="hint">لا توجد فنادق نشطة في منطقة الرحلة. اربط فندقًا بالمنطقة أو فعّله أولًا.</p>'
            : `<div class="tbl-wrap">
                <table class="tbl trip-hotel-table" data-trip-options="${Number(trip.id)}">
                  <thead>
                    <tr><th>الفندق</th><th>ربط بالرحلة</th><th>وقت الاستلام</th></tr>
                  </thead>
                  <tbody>
                    ${hotels.map(hotel => {
                      const assignment = assignmentByHotel.get(Number(hotel.id));
                      const selected = Boolean(assignment);
                      return `<tr class="trip-hotel-option">
                        <td>${esc(hotel.name)}</td>
                        <td>
                          <label class="trip-hotel-choice">
                            <input type="checkbox" value="${Number(hotel.id)}" ${selected ? 'checked' : ''}>
                            <span>${selected ? 'مرتبط' : 'غير مرتبط'}</span>
                          </label>
                        </td>
                        <td>
                          <input type="time" data-pickup-time="${Number(hotel.id)}"
                            value="${esc((assignment?.pickup_time || '').slice(0, 5))}" ${selected ? '' : 'disabled'}
                            aria-label="وقت الاستلام في ${esc(hotel.name)}">
                        </td>
                      </tr>`;
                    }).join('')}
                  </tbody>
                </table>
              </div>`}
      </div>
    </section>`;
  }).join('');
}

document.getElementById('trip-search').addEventListener('input', renderTripHotelList);
document.getElementById('trip-hotels-list').addEventListener('change', event => {
  const checkbox = event.target.closest('.trip-hotel-option input[type="checkbox"]');
  if (!checkbox) return;

  const row = checkbox.closest('.trip-hotel-option');
  const timeInput = row.querySelector('[data-pickup-time]');
  timeInput.disabled = !checkbox.checked;
  checkbox.nextElementSibling.textContent = checkbox.checked ? 'مرتبط' : 'غير مرتبط';
});

document.getElementById('trip-hotels-list').addEventListener('click', async event => {
  const button = event.target.closest('[data-save-trip]');
  if (!button) return;

  const tripId = Number(button.dataset.saveTrip);
  const options = document.querySelector(`[data-trip-options="${tripId}"]`);
  const selectedHotels = options
    ? [...options.querySelectorAll('.trip-hotel-option')]
      .filter(option => option.querySelector('input[type="checkbox"]').checked)
    : [];
  const hotelIds = selectedHotels.map(option => Number(option.querySelector('input[type="checkbox"]').value));
  const hotelTimes = Object.fromEntries(selectedHotels.map(option => {
    const timeInput = option.querySelector('[data-pickup-time]');
    return [timeInput.dataset.pickupTime, timeInput.value || null];
  }));
  button.disabled = true;

  try {
    await api(`/api/trips/${tripId}/hotels`, { method: 'PUT', body: { hotel_ids: hotelIds, hotel_times: hotelTimes } });
    const trip = TRIP_HOTEL_DATA.trips.find(item => Number(item.id) === tripId);
    TRIP_HOTEL_DATA.assignments = (TRIP_HOTEL_DATA.assignments || [])
      .filter(assignment => Number(assignment.trip_id) !== tripId)
      .concat(hotelIds.map(hotelId => ({
        trip_id: tripId,
        hotel_id: hotelId,
        pickup_time: hotelTimes[hotelId] || null,
      })));
    toast(`تم حفظ فنادق رحلة ${trip?.name || ''}`);
  } catch (error) {
    toast(error.message, 'err');
  } finally {
    button.disabled = false;
  }
});

loadTripHotelData();
</script>
<style>
.trip-hotel-table td{vertical-align:middle}
.trip-hotel-choice{display:flex;align-items:center;gap:.6rem;cursor:pointer}
.trip-hotel-choice input{width:1.1rem;height:1.1rem;accent-color:#0f766e}
.trip-hotel-table input[type="time"]{max-width:150px;padding:.4rem;border:1px solid #d0d5dd;border-radius:.45rem}
.trip-hotel-table input[type="time"]:disabled{background:#f2f4f7;color:#98a2b3}
</style>
@endsection
