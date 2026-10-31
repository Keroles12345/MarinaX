@extends('admin.layouts.app')
@section('title')سجل العمليات@endsection
@section('page_title')سجل العمليات@endsection
@section('content')

<div class="panel">
  <div class="panel-head">
    <h3>🛡️ سجل العمليات — {{ number_format($logs->total()) }} نتيجة</h3>
  </div>
  <form method="get" action="{{ route('accounting.audit') }}" class="filters">
    <div class="field">
      <label for="audit-q">بحث</label>
      <input id="audit-q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="المستخدم أو العملية أو التفاصيل">
    </div>
    <div class="field">
      <label for="audit-from">من تاريخ</label>
      <input id="audit-from" name="from" type="date" value="{{ $filters['from'] ?? '' }}">
    </div>
    <div class="field">
      <label for="audit-to">إلى تاريخ</label>
      <input id="audit-to" name="to" type="date" value="{{ $filters['to'] ?? '' }}">
    </div>
    <button class="btn btn-teal" type="submit">🔍 بحث</button>
    <a class="btn btn-dark" href="{{ route('accounting.audit') }}">مسح الفلاتر</a>
  </form>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th class="num">التاريخ والوقت</th><th>المستخدم</th><th>العملية</th><th>التفاصيل</th></tr></thead>
      <tbody>
        @forelse($logs as $l)
        <tr>
          <td class="num">{{ $l->at }}</td>
          <td>{{ $l->user }}</td>
          <td><span class="badge region">{{ $l->action }}</span></td>
          <td>{{ $l->details ?: '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="empty-state">لا توجد عمليات مسجلة بعد</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="panel-body">{{ $logs->links() }}</div>
</div>

@endsection
