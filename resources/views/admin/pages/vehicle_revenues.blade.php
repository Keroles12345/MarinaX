@extends('admin.layouts.app')
@section('title')إيرادات السيارات والأتوبيسات@endsection
@section('page_title')إيرادات السيارات والأتوبيسات@endsection
@section('content')
<style>
  .vehicle-revenue-panel{background:#fff;border:1px solid #e3e8ef;border-radius:12px;padding:16px;margin-top:16px}
  .vehicle-revenue-filters{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;align-items:end}
  .vehicle-revenue-filters label{display:grid;gap:6px;font-size:13px;font-weight:600}
  .vehicle-revenue-input{width:100%;padding:10px 12px;border:1px solid #d7dee8;border-radius:9px;background:#fff;color:#172033}
  .vehicle-revenue-submit{border:0;border-radius:9px;background:#147d73;color:#fff;padding:11px 16px;font-weight:700;cursor:pointer}
  .vehicle-revenue-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px;margin:18px 0}
  .vehicle-revenue-card{background:#fff;border:1px solid #e3e8ef;border-radius:12px;padding:16px}
  .vehicle-revenue-label{color:#667085;font-size:13px;margin-bottom:8px}
  .vehicle-revenue-value{font-size:22px;font-weight:700;color:#173b56}
  .vehicle-revenue-scroll{overflow:auto}
  .vehicle-revenue-table{width:100%;border-collapse:collapse}
  .vehicle-revenue-table th,.vehicle-revenue-table td{padding:10px;border-bottom:1px solid #edf0f4;text-align:right;white-space:nowrap}
  .vehicle-revenue-table th{color:#667085;font-size:12px}
</style>

<p class="hint">إيرادات السيارات والأتوبيسات محسوبة من الجانب الدائن لحساب إيراد كل مركبة في القيود المرحلة. التحصيل النقدي أو المبالغ الموجودة بعهدة الموظفين تظهر في حساباتها المنفصلة، وليست إيرادًا للصندوق.</p>

<div class="vehicle-revenue-panel">
  <form class="vehicle-revenue-filters" method="GET" action="{{ route('vehicle-revenues.index') }}">
    <label>نوع الملكية
      <select class="vehicle-revenue-input" name="ownership_type" id="vehicle-ownership-filter">
        <option value="">كل السيارات والأتوبيسات</option>
        <option value="internal" @selected(($filters['ownership_type'] ?? '') === 'internal')>داخلي</option>
        <option value="external" @selected(($filters['ownership_type'] ?? '') === 'external')>خارجي</option>
      </select>
    </label>
    <label>السيارة / الأتوبيس
      <select class="vehicle-revenue-input" name="vehicle_id" id="vehicle-resource-filter">
        <option value="">كل السيارات والأتوبيسات</option>
        @foreach($vehicleOptions as $vehicle)
          <option value="{{ $vehicle->id }}" data-ownership="{{ $vehicle->ownership_type }}" @selected((string) ($filters['vehicle_id'] ?? '') === (string) $vehicle->id)>{{ $vehicle->name }} · {{ $vehicle->code }}{{ $vehicle->status !== 'active' ? ' (مؤرشفة)' : '' }}</option>
        @endforeach
      </select>
    </label>
    <label>من تاريخ
      <input class="vehicle-revenue-input" type="date" name="from" value="{{ $filters['from'] ?? '' }}">
    </label>
    <label>إلى تاريخ
      <input class="vehicle-revenue-input" type="date" name="to" value="{{ $filters['to'] ?? '' }}">
    </label>
    <button class="vehicle-revenue-submit" type="submit">عرض الإيرادات</button>
  </form>
</div>

<script>
(() => {
  const ownershipFilter = document.getElementById('vehicle-ownership-filter');
  const vehicleFilter = document.getElementById('vehicle-resource-filter');
  const filterVehicles = () => {
    [...vehicleFilter.options].forEach(option => {
      if (!option.value) return;
      option.hidden = ownershipFilter.value !== '' && option.dataset.ownership !== ownershipFilter.value;
    });
    const selected = vehicleFilter.selectedOptions[0];
    if (selected?.hidden) vehicleFilter.value = '';
  };
  ownershipFilter.addEventListener('change', filterVehicles);
  filterVehicles();
})();
</script>

<div class="vehicle-revenue-grid">
  <div class="vehicle-revenue-card">
    <div class="vehicle-revenue-label">إجمالي إيرادات السيارات والأتوبيسات في الفترة</div>
    <div class="vehicle-revenue-value">{{ number_format($periodTotal, 2) }} ج.م</div>
  </div>
  @if($selectedVehicle)
    <div class="vehicle-revenue-card">
      <div class="vehicle-revenue-label">إيراد {{ $selectedVehicle->name }}</div>
      <div class="vehicle-revenue-value">{{ number_format($revenueTotal, 2) }} ج.م</div>
    </div>
  @endif
</div>

@if($selectedVehicle)
  <div class="vehicle-revenue-panel">
    <h3 style="margin:0 0 14px">تفاصيل إيراد {{ $selectedVehicle->name }}</h3>
    <div class="vehicle-revenue-scroll">
      <table class="vehicle-revenue-table">
        <thead><tr><th>التاريخ</th><th>رقم الحجز</th><th>رقم الحركة</th><th>البيان</th><th>الإيراد</th></tr></thead>
        <tbody>
          @forelse($entries as $entry)
            <tr>
              <td>{{ $entry->entry_date }}</td>
              <td>{{ $entry->booking_id ?? '—' }}</td>
              <td>{{ $entry->movement_no }}</td>
              <td>{{ $entry->description ?: '—' }}</td>
              <td>{{ number_format((float) $entry->revenue_amount, 2) }} ج.م</td>
            </tr>
          @empty
            <tr><td colspan="5" class="hint">لا توجد إيرادات مرحلة لهذه المركبة في الفترة المحددة.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($entries instanceof \Illuminate\Contracts\Pagination\Paginator)
      <div style="margin-top:14px">{{ $entries->links() }}</div>
    @endif
  </div>
@else
  <div class="vehicle-revenue-panel">
    <h3 style="margin:0 0 14px">إيرادات السيارات والأتوبيسات</h3>
    <div class="vehicle-revenue-scroll">
      <table class="vehicle-revenue-table">
        <thead><tr><th>السيارة / الأتوبيس</th><th>الكود</th><th>النوع</th><th>نوع الملكية</th><th>حساب الإيراد</th><th>إيراد الفترة</th></tr></thead>
        <tbody>
          @forelse($vehicles as $vehicle)
            <tr>
              <td>{{ $vehicle->name }}{{ $vehicle->status !== 'active' ? ' (مؤرشفة)' : '' }}</td>
              <td>{{ $vehicle->code }}</td>
              <td>{{ $vehicle->vehicle_type_name ?? '—' }}</td>
              <td>{{ $vehicle->ownership_type === 'external' ? 'خارجي' : 'داخلي' }}</td>
              <td>{{ $vehicle->acc_no ?? '—' }}</td>
              <td>{{ number_format($vehicle->revenue_total, 2) }} ج.م</td>
            </tr>
          @empty
            <tr><td colspan="6" class="hint">لا توجد سيارات أو أتوبيسات مسجلة.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endif
@endsection
