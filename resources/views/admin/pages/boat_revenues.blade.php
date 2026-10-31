@extends('admin.layouts.app')
@section('title')إيرادات المراكب@endsection
@section('page_title')إيرادات المراكب@endsection
@section('content')
<style>
  .boat-revenue-panel{background:#fff;border:1px solid #e3e8ef;border-radius:12px;padding:16px;margin-top:16px}
  .boat-revenue-filters{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;align-items:end}
  .boat-revenue-filters label{display:grid;gap:6px;font-size:13px;font-weight:600}
  .boat-revenue-input{width:100%;padding:10px 12px;border:1px solid #d7dee8;border-radius:9px;background:#fff;color:#172033}
  .boat-revenue-submit{border:0;border-radius:9px;background:#147d73;color:#fff;padding:11px 16px;font-weight:700;cursor:pointer}
  .boat-revenue-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px;margin:18px 0}
  .boat-revenue-card{background:#fff;border:1px solid #e3e8ef;border-radius:12px;padding:16px}
  .boat-revenue-label{color:#667085;font-size:13px;margin-bottom:8px}
  .boat-revenue-value{font-size:22px;font-weight:700;color:#173b56}
  .boat-revenue-scroll{overflow:auto}
  .boat-revenue-table{width:100%;border-collapse:collapse}
  .boat-revenue-table th,.boat-revenue-table td{padding:10px;border-bottom:1px solid #edf0f4;text-align:right;white-space:nowrap}
  .boat-revenue-table th{color:#667085;font-size:12px}
</style>

<p class="hint">إيرادات المراكب محسوبة من الجانب الدائن لحساب إيراد كل مركب في القيود المرحلة. التحصيل النقدي أو المبالغ الموجودة بعهدة الموظفين تظهر في حساباتها المنفصلة، وليست إيرادًا للصندوق.</p>

<div class="boat-revenue-panel">
  <form class="boat-revenue-filters" method="GET" action="{{ route('boat-revenues.index') }}">
    <label>نوع الملكية
      <select class="boat-revenue-input" name="ownership_type" id="boat-ownership-filter">
        <option value="">كل المراكب</option>
        <option value="internal" @selected(($filters['ownership_type'] ?? '') === 'internal')>داخلي</option>
        <option value="external" @selected(($filters['ownership_type'] ?? '') === 'external')>خارجي</option>
      </select>
    </label>
    <label>المركب
      <select class="boat-revenue-input" name="boat_id" id="boat-resource-filter">
        <option value="">كل المراكب</option>
        @foreach($boatOptions as $boat)
          <option value="{{ $boat->id }}" data-ownership="{{ $boat->ownership_type }}" @selected((string) ($filters['boat_id'] ?? '') === (string) $boat->id)>{{ $boat->name }} · {{ $boat->code }}{{ $boat->is_active ? '' : ' (مؤرشف)' }}</option>
        @endforeach
      </select>
    </label>
    <label>من تاريخ
      <input class="boat-revenue-input" type="date" name="from" value="{{ $filters['from'] ?? '' }}">
    </label>
    <label>إلى تاريخ
      <input class="boat-revenue-input" type="date" name="to" value="{{ $filters['to'] ?? '' }}">
    </label>
    <button class="boat-revenue-submit" type="submit">عرض الإيرادات</button>
  </form>
</div>

<script>
(() => {
  const ownershipFilter = document.getElementById('boat-ownership-filter');
  const boatFilter = document.getElementById('boat-resource-filter');
  const filterBoats = () => {
    [...boatFilter.options].forEach(option => {
      if (!option.value) return;
      option.hidden = ownershipFilter.value !== '' && option.dataset.ownership !== ownershipFilter.value;
    });
    const selected = boatFilter.selectedOptions[0];
    if (selected?.hidden) boatFilter.value = '';
  };
  ownershipFilter.addEventListener('change', filterBoats);
  filterBoats();
})();
</script>

<div class="boat-revenue-grid">
  <div class="boat-revenue-card">
    <div class="boat-revenue-label">إجمالي إيرادات كل المراكب في الفترة</div>
    <div class="boat-revenue-value">{{ number_format($periodTotal, 2) }} ج.م</div>
  </div>
  @if($selectedBoat)
    <div class="boat-revenue-card">
      <div class="boat-revenue-label">إيراد {{ $selectedBoat->name }}</div>
      <div class="boat-revenue-value">{{ number_format($revenueTotal, 2) }} ج.م</div>
    </div>
  @endif
</div>

@if($selectedBoat)
  <div class="boat-revenue-panel">
    <h3 style="margin:0 0 14px">تفاصيل إيراد {{ $selectedBoat->name }}</h3>
    <div class="boat-revenue-scroll">
      <table class="boat-revenue-table">
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
            <tr><td colspan="5" class="hint">لا توجد إيرادات مرحلة لهذا المركب في الفترة المحددة.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($entries instanceof \Illuminate\Contracts\Pagination\Paginator)
      <div style="margin-top:14px">{{ $entries->links() }}</div>
    @endif
  </div>
@else
  <div class="boat-revenue-panel">
    <h3 style="margin:0 0 14px">إيرادات المراكب</h3>
    <div class="boat-revenue-scroll">
      <table class="boat-revenue-table">
        <thead><tr><th>المركب</th><th>الكود</th><th>نوع الملكية</th><th>حساب الإيراد</th><th>إيراد الفترة</th></tr></thead>
        <tbody>
          @forelse($boats as $boat)
            <tr>
              <td>{{ $boat->name }}{{ $boat->is_active ? '' : ' (مؤرشف)' }}</td>
              <td>{{ $boat->code }}</td>
              <td>{{ $boat->ownership_type === 'external' ? 'خارجي' : 'داخلي' }}</td>
              <td>{{ $boat->acc_no ?? '—' }}</td>
              <td>{{ number_format($boat->revenue_total, 2) }} ج.م</td>
            </tr>
          @empty
            <tr><td colspan="5" class="hint">لا توجد مراكب مسجلة.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endif
@endsection
