@extends('admin.layouts.app')
@section('title')أسعار الشركات@endsection
@section('page_title')أسعار الشركات@endsection
@section('content')

@if(session('status'))<div class="alert ok">{{ session('status') }}</div>@endif

<div class="panel">
  <div class="panel-head">
    <h3>أسعار الشركات والرحلات — {{ $prices->count() }}</h3>
    <span class="badge posted">العملة الأساسية: {{ $currency->name }} ({{ $currency->code }})</span>
    <button class="btn btn-gold" type="button" onclick="openPriceModal()">＋ إضافة / تعديل سعر</button>
  </div>
  <div class="panel-body hint">الأسعار تُسجل بالجنيه المصري، ثم تُحوّل لعملة الحجز عند تسجيل العملية.</div>
  <div class="panel-head"><input id="price-search" type="search" class="search-inline" placeholder="🔍 بحث باسم الشركة أو الرحلة..."></div>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>الشركة</th><th>الرحلة</th><th>البالغ (EGP)</th><th>الطفل (EGP)</th><th>الرضيع (EGP)</th><th>الإجراء</th></tr></thead>
      <tbody>
        @forelse($prices as $price)
          <tr data-price-row data-search="{{ mb_strtolower($price->company_name.' '.$price->trip_name) }}">
            <td>{{ $price->company_name }}</td>
            <td>{{ $price->trip_name }}</td>
            <td class="num">{{ number_format((float) $price->price_adult, 2) }}</td>
            <td class="num">{{ number_format((float) $price->price_child, 2) }}</td>
            <td class="num">{{ number_format((float) $price->price_infant, 2) }}</td>
            <td>
              <button class="btn btn-dark btn-sm" type="button" data-edit-price
                data-company="{{ $price->id_company }}" data-trip="{{ $price->id_trip }}"
                data-adult="{{ $price->price_adult }}" data-child="{{ $price->price_child }}"
                data-infant="{{ $price->price_infant }}">تعديل الأسعار</button>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="empty-state">لا توجد أسعار مسجلة بعد.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay {{ $errors->any() ? 'open' : '' }}" id="company-price-modal" role="dialog" aria-modal="true" aria-labelledby="company-price-title">
  <div class="modal">
    <div class="modal-head">
      <h3 id="company-price-title">إضافة أو تعديل أسعار شركة</h3>
      <button class="modal-close" type="button" aria-label="إغلاق" onclick="closePriceModal()">×</button>
    </div>
    <form method="POST" action="{{ route('accounting.company-prices.save') }}">
      @csrf
      <div class="modal-body">
        @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
        <div class="field">
          <label for="price-company">الشركة *</label>
          <select id="price-company" name="company_id" required>
            <option value="">اختر الشركة</option>
            @foreach($companies as $company)
              <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>{{ $company->name }}</option>
            @endforeach
          </select>
          @error('company_id')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror
        </div>
        <div class="field">
          <label for="price-trip">الرحلة *</label>
          <select id="price-trip" name="trip_id" required>
            <option value="">اختر الرحلة</option>
            @foreach($trips as $trip)
              <option value="{{ $trip->id }}" @selected(old('trip_id') == $trip->id)>{{ $trip->name }}</option>
            @endforeach
          </select>
          @error('trip_id')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror
        </div>
        <div class="field"><label>العملة الأساسية</label><input value="{{ $currency->name }} ({{ $currency->code }})" readonly></div>
        <div class="grid-2">
          <div class="field"><label for="price-adult">سعر البالغ *</label><input id="price-adult" type="number" name="price_adult" min="0" max="99999999.99" step="0.01" required value="{{ old('price_adult', '0.00') }}">@error('price_adult')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror</div>
          <div class="field"><label for="price-child">سعر الطفل *</label><input id="price-child" type="number" name="price_child" min="0" max="99999999.99" step="0.01" required value="{{ old('price_child', '0.00') }}">@error('price_child')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror</div>
        </div>
        <div class="field"><label for="price-infant">سعر الرضيع *</label><input id="price-infant" type="number" name="price_infant" min="0" max="99999999.99" step="0.01" required value="{{ old('price_infant', '0.00') }}">@error('price_infant')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror</div>
      </div>
      <div class="modal-foot">
        <button class="btn btn-gold" type="submit">حفظ الأسعار بالجنيه</button>
        <button class="btn btn-dark" type="button" onclick="closePriceModal()">إلغاء</button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
function openPriceModal() {
  document.getElementById('company-price-modal').classList.add('open');
}
function closePriceModal() {
  document.getElementById('company-price-modal').classList.remove('open');
}

document.querySelectorAll('[data-edit-price]').forEach(button => button.addEventListener('click', () => {
  document.getElementById('price-company').value = button.dataset.company;
  document.getElementById('price-trip').value = button.dataset.trip;
  document.getElementById('price-adult').value = button.dataset.adult;
  document.getElementById('price-child').value = button.dataset.child;
  document.getElementById('price-infant').value = button.dataset.infant;
  openPriceModal();
}));

document.getElementById('price-search').addEventListener('input', event => {
  const query = event.target.value.trim().toLowerCase();
  document.querySelectorAll('[data-price-row]').forEach(row => {
    row.hidden = query !== '' && !row.dataset.search.includes(query);
  });
});
</script>
@endsection
