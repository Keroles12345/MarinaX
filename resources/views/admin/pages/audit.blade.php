@extends('admin.layouts.app')
@section('title')سجل العمليات@endsection
@section('page_title')سجل العمليات@endsection
@section('content')

<div class="panel">
  <div class="panel-head">
    <h3>🛡️ {{ __('سجل العمليات — :count نتيجة', ['count' => number_format($logs->total())]) }}</h3>
  </div>
  <form method="get" action="{{ route('accounting.audit') }}" class="filters">
    <div class="field">
      <label for="audit-q">{{ __('بحث') }}</label>
      <input id="audit-q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('المستخدم أو العملية أو التفاصيل') }}">
    </div>
    <div class="field">
      <label for="audit-from">{{ __('من تاريخ') }}</label>
      <input id="audit-from" name="from" type="date" value="{{ $filters['from'] ?? '' }}">
    </div>
    <div class="field">
      <label for="audit-to">{{ __('إلى تاريخ') }}</label>
      <input id="audit-to" name="to" type="date" value="{{ $filters['to'] ?? '' }}">
    </div>
    <button class="btn btn-teal" type="submit">🔍 {{ __('بحث') }}</button>
    <a class="btn btn-dark" href="{{ route('accounting.audit') }}">{{ __('مسح الفلاتر') }}</a>
  </form>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th class="num">{{ __('التاريخ والوقت') }}</th><th>{{ __('المستخدم') }}</th><th>{{ __('العملية') }}</th><th>{{ __('التفاصيل') }}</th></tr></thead>
      <tbody id="audit-rows">
        @if($logs->isEmpty())
          <tr><td colspan="4" class="empty-state">{{ __('لا توجد عمليات مسجلة بعد') }}</td></tr>
        @else
          @include('admin.pages.partials.audit-rows', ['logs' => $logs])
        @endif
      </tbody>
    </table>
  </div>
  <div
    class="infinite-scroll audit-load-more"
    data-infinite-scroll
    data-rows="#audit-rows"
    data-summary="#audit-summary"
    data-next-page="{{ $logs->nextPageUrl() }}"
    data-has-more="{{ $logs->hasMorePages() ? 'true' : 'false' }}"
    data-loading-text="{{ __('جاري تحميل المزيد...') }}"
    data-more-text="{{ __('تحميل المزيد') }}"
    data-retry-text="{{ __('تعذّر تحميل السجلات. حاول مرة أخرى.') }}"
    data-end-text="{{ __('تم تحميل كل النتائج') }}"
    role="status"
    aria-live="polite"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
  >
    @if($logs->isNotEmpty())
      <p class="audit-pagination-summary" id="audit-summary">
        {{ __('عرض :from إلى :to من أصل :total عملية', [
          'from' => number_format($logs->firstItem()),
          'to' => number_format($logs->lastItem()),
          'total' => number_format($logs->total()),
        ]) }}
      </p>
    @endif
    <span class="audit-load-message" data-load-message role="status" aria-live="polite">
      <span class="audit-loading-spinner" data-load-spinner aria-hidden="true" hidden></span>
      <span data-load-message-text></span>
    </span>
    <button class="btn btn-dark audit-load-button" data-load-more-button type="button">{{ __('تحميل المزيد') }}</button>
    <span class="audit-load-sentinel" data-load-sentinel aria-hidden="true"></span>
  </div>
</div>

@endsection
