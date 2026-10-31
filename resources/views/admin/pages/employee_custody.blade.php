@extends('admin.layouts.app')
@section('title')عهد الموظفين@endsection
@section('page_title')عهد الموظفين@endsection
@section('content')
<style>
  .custody-select,.custody-input{width:100%;padding:10px 12px;border:1px solid #d7dee8;border-radius:9px;background:#fff;color:#172033}
  .custody-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(175px,1fr));gap:14px;margin:18px 0}
  .custody-card,.custody-panel{background:#fff;border:1px solid #e3e8ef;border-radius:12px;padding:16px}
  .custody-label{color:#667085;font-size:13px;margin-bottom:8px}
  .custody-value{font-size:22px;font-weight:700;color:#173b56}
  .custody-panel{margin-top:18px}
  .custody-panel h3{margin:0 0 14px}
  .custody-form{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;align-items:end}
  .custody-form label{display:grid;gap:6px;font-size:13px;font-weight:600}
  .custody-submit{border:0;border-radius:9px;background:#147d73;color:#fff;padding:11px 16px;font-weight:700;cursor:pointer}
  .custody-table{width:100%;border-collapse:collapse}
  .custody-table th,.custody-table td{padding:10px;border-bottom:1px solid #edf0f4;text-align:right;white-space:nowrap}
  .custody-table th{color:#667085;font-size:12px}
  .custody-scroll{overflow:auto}
  .custody-alert{padding:12px 14px;border-radius:9px;margin:12px 0}
  .custody-error{background:#fff1f0;color:#a61b1b}
  .custody-success{background:#eaf8f0;color:#176b3a}
</style>

<p class="hint">تابع مبيعات كل موظف والمبالغ المحصلة في عهدته، وسجّل ما يستلمه المحاسب في الصندوق.</p>
@if(session('status'))<div class="custody-alert custody-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="custody-alert custody-error"><ul style="margin:0;padding-right:20px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="custody-panel">
  <form method="GET" action="{{ route('employee-custody.index') }}">
    <label style="display:grid;gap:8px;max-width:520px;font-weight:700">اختار الموظف
      <select class="custody-select" name="employee_id" onchange="this.form.submit()">
        <option value="">اختار موظف لعرض التفاصيل</option>
        @foreach($employees as $item)
          <option value="{{ $item->id }}" @selected((string) $selectedEmployeeId === (string) $item->id)>{{ $item->name }}{{ $item->position ? ' — '.$item->position : '' }}</option>
        @endforeach
      </select>
    </label>
  </form>
</div>

@if($employee)
  <div class="custody-panel">
    <h3>{{ $employee->name }}</h3>
    <p class="hint" style="margin:0">
      {{ $employeeProfile->position ?? 'المسمى الوظيفي غير محدد' }}
      · {{ $employeeProfile->department_name ?? 'القسم غير محدد' }}
      · {{ $employeeProfile->phone ?? 'لا يوجد هاتف مسجل' }}
      · {{ $employee->email ?: $employee->username }}
    </p>
  </div>
  <div class="custody-grid">
    <div class="custody-card"><div class="custody-label">إجمالي المبيعات</div><div class="custody-value">{{ number_format($salesTotal, 2) }} ج.م</div></div>
    <div class="custody-card"><div class="custody-label">إجمالي المحصل</div><div class="custody-value">{{ number_format($collectedTotal, 2) }} ج.م</div></div>
    <div class="custody-card"><div class="custody-label">المُورّد للصندوق</div><div class="custody-value">{{ number_format($depositedTotal, 2) }} ج.م</div></div>
    <div class="custody-card"><div class="custody-label">رصيد العهدة الحالي</div><div class="custody-value">{{ number_format($custodyBalance, 2) }} ج.م</div></div>
  </div>

  <div class="custody-panel">
    <h3>تسجيل استلام عهدة من {{ $employee->name }}</h3>
    <form class="custody-form" method="POST" action="{{ route('employee-custody.deposit') }}">
      @csrf
      <input type="hidden" name="employee_id" value="{{ $employee->id }}">
      <label>المبلغ بالجنيه
        <input class="custody-input" type="number" name="amount" min="0.01" max="{{ max($custodyBalance, 0) }}" step="0.01" value="{{ old('amount') }}" required>
      </label>
      <label>تاريخ الاستلام
        <input class="custody-input" type="date" name="deposit_date" value="{{ old('deposit_date', today()->toDateString()) }}" required>
      </label>
      <label>المنطقة
        <select class="custody-select" name="region" required>
          <option value="">اختار المنطقة</option>
          @foreach($regions as $region)<option value="{{ $region }}" @selected(old('region') === $region)>{{ $region }}</option>@endforeach
        </select>
      </label>
      <label>ملاحظات
        <input class="custody-input" type="text" name="notes" maxlength="1000" value="{{ old('notes') }}">
      </label>
      <button class="custody-submit" type="submit" @disabled($custodyBalance <= 0 || $regions->isEmpty())>تسجيل التوريد للصندوق</button>
    </form>
    @if($regions->isEmpty())<p class="hint">لا توجد منطقة مفعّلة. فعّل منطقة من صفحة إدارة المناطق قبل تسجيل التوريد.</p>@endif
  </div>

  <div class="custody-panel">
    <h3>مبيعات الموظف</h3>
    <div class="custody-scroll"><table class="custody-table">
      <thead><tr><th>رقم الحجز</th><th>العميل</th><th>الرحلة</th><th>تاريخ العملية</th><th>إجمالي البيع</th><th>المبلغ المحصل</th><th>حالة السداد</th><th>الترحيل</th></tr></thead>
      <tbody>
        @forelse($sales as $sale)
          <tr>
            <td>{{ $sale->booking_id }}</td><td>{{ $sale->customer_name ?? '—' }}</td><td>{{ $sale->trip_name ?? '—' }}</td><td>{{ $sale->trip_date }}</td>
            <td>{{ number_format($sale->totalamount, 2) }} ج.م</td>
            <td>{{ number_format($sale->collected_amount, 2) }} ج.م</td>
            <td>{{ $sale->status_payment }}</td><td>{{ $sale->accounting_entry_id ? 'مرحل' : 'بانتظار الترحيل' }}</td>
          </tr>
        @empty
          <tr><td colspan="8" class="hint">لا توجد عمليات مكتملة لهذا الموظف.</td></tr>
        @endforelse
      </tbody>
    </table></div>
  </div>

  <div class="custody-panel">
    <h3>سجل التوريد للصندوق</h3>
    <div class="custody-scroll"><table class="custody-table">
      <thead><tr><th>التاريخ</th><th>المبلغ</th><th>المنطقة</th><th>استلمه</th><th>ملاحظات</th></tr></thead>
      <tbody>
        @forelse($deposits as $deposit)
          <tr><td>{{ $deposit->deposit_date }}</td><td>{{ number_format((float) $deposit->amount, 2) }} ج.م</td><td>{{ $deposit->region }}</td><td>{{ $deposit->receiver_name ?? '—' }}</td><td>{{ $deposit->notes ?: '—' }}</td></tr>
        @empty
          <tr><td colspan="5" class="hint">لم يتم تسجيل توريدات لهذا الموظف.</td></tr>
        @endforelse
      </tbody>
    </table></div>
  </div>
@endif
@endsection
