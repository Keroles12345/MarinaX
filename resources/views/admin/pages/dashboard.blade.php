@extends('admin.layouts.app')
@section('title')لوحة التحكم@endsection
@section('page_title')لوحة التحكم@endsection
@section('content')

<div class="quick-actions">
  @systemPermission('journal', 'create')
  <a href="/journal" class="btn btn-gold">➕ {{ __('قيد يومية جديد') }}</a>
  @endsystemPermission
  @systemPermission('reports', 'view')
  <a href="/ledger" class="btn btn-teal">📖 {{ __('كشف حساب') }}</a>
  <a href="/trial-balance" class="btn btn-dark">⚖️ {{ __('ميزان المراجعة') }}</a>
  @endsystemPermission
  @if(in_array($user->role, ['admin', 'accountant'], true))
  @systemPermission('employee_custody', 'view')
  <a href="{{ route('employee-custody.index') }}" class="btn btn-teal">💼 {{ __('عهد الموظفين') }}</a>
  @endsystemPermission
  @endif
  @systemPermission('boat_revenues', 'view')
  <a href="{{ route('boat-revenues.index') }}" class="btn btn-teal">⛵ {{ __('إيرادات المراكب') }}</a>
  @endsystemPermission
  @systemPermission('vehicle_revenues', 'view')
  <a href="{{ route('vehicle-revenues.index') }}" class="btn btn-teal">🚐 {{ __('إيرادات السيارات والأتوبيسات') }}</a>
  @endsystemPermission
  @if($user->role === 'admin')
  @systemPermission('users', 'view')
  <a href="{{ route('admin.user-permissions') }}" class="btn btn-dark">🔐 {{ __('صلاحيات المستخدمين') }}</a>
  @endsystemPermission
  @systemPermission('regions', 'view')
  <a href="{{ route('accounting.regions') }}" class="btn btn-dark">🗺️ {{ __('إدارة المناطق') }}</a>
  @endsystemPermission
  @systemPermission('boats', 'view')
  <a href="{{ route('accounting.boats') }}" class="btn btn-teal">⛵ {{ __('صفحة الأدمن - المراكب') }}</a>
  @endsystemPermission
  @systemPermission('vehicles', 'view')
  <a href="{{ route('accounting.vehicles') }}" class="btn btn-dark">🚐 {{ __('صفحة الأدمن - السيارات والتوبيسات') }}</a>
  @endsystemPermission
  @endif
</div>

<div class="cards-grid">    
  <div class="card">
    <div class="lbl">{{ __('حسابات دليل الحسابات') }}</div>
    <div class="val gold" id="st-accounts">—</div>
  </div>
  <div class="card teal">
    <div class="lbl">{{ __('إجمالي القيود') }}</div>
    <div class="val teal" id="st-entries">—</div>
  </div>
  <div class="card green">
    <div class="lbl">{{ __('إجمالي المدين (المرحل)') }}</div>
    <div class="val green" id="st-debit">—</div>
  </div>
  <div class="card red">
    <div class="lbl">{{ __('إجمالي الدائن (المرحل)') }}</div>
    <div class="val red" id="st-credit">—</div>
  </div>
  <div class="card">
    <div class="lbl">{{ __('قيود غير مرحلة') }}</div>
    <div class="val" id="st-drafts" style="color:var(--amber)">—</div>
  </div>
</div>

<div class="grid-2">
  <div class="panel">
    <div class="panel-head"><h3>🕒 {{ __('أحدث القيود') }}</h3><a class="btn btn-sm btn-dark" href="/journal">{{ __('الكل') }}</a></div>
    <div class="tbl-wrap">
      <table class="tbl">
        <thead><tr><th>{{ __('رقم الحركة') }}</th><th>{{ __('التاريخ') }}</th><th>{{ __('البيان') }}</th><th>{{ __('المدين') }}</th><th>{{ __('الحالة') }}</th></tr></thead>
        <tbody id="recent-body"></tbody>
      </table>
    </div>
  </div>
  <div class="panel">
    <div class="panel-head"><h3>ℹ️ {{ __('معلومات الفترة الحالية') }}</h3></div>
    <div class="panel-body hint" style="font-size:13.5px">
      <p style="margin-bottom:10px">📅 {{ __('فترة التقارير:') }} <b style="color:var(--text)">{{ $periodFrom }}</b> {{ __('إلى') }} <b style="color:var(--text)">{{ $periodTo }}</b></p>
      <p style="margin-bottom:10px">🏢 {{ __('الشركة:') }} <b style="color:var(--text)">{{ $companyName }}</b></p>
      <p style="margin-bottom:10px">🗺️ {{ __('المناطق:') }} {{ __('المركز الرئيسي، المنطقة المركزية، المنطقة الشمالية، المنطقة الشرقية، المنطقة الجنوبية') }}</p>
      <p>💡 {{ __('القيود غير المرحلة لا تظهر في الأستاذ العام وميزان المراجعة حتى يتم ترحيلها من صفحة دفتر اليومية.') }}</p>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
(async () => {
  try {
    const d = await api('/api/home');
    $('#st-accounts').textContent = d.stats.accounts;
    $('#st-entries').textContent = d.stats.entries;
    $('#st-debit').textContent = fmt(d.stats.total_debit);
    $('#st-credit').textContent = fmt(d.stats.total_credit);
    $('#st-drafts').textContent = d.stats.drafts;
    const tb = $('#recent-body');
    if (!d.recent.length) {
      tb.innerHTML = `<tr><td colspan="5" class="empty-state"><div class="big">📭</div>${@json(__('لا توجد قيود بعد'))}</td></tr>`;
    } else {
      tb.innerHTML = d.recent.map(e => `
        <tr>
          <td class="num">${esc(e.movement_no)}</td>
          <td class="num">${fmtDate(e.entry_date)}</td>
          <td>${esc(e.description || '—')}</td>
          <td class="num">${fmt(e.total_debit)}</td>
          <td>${e.status === 'posted' ? '<span class="badge posted">{{ __('مرحل') }}</span>' : '<span class="badge draft">{{ __('مسودة') }}</span>'}</td>
        </tr>`).join('');
    }
  } catch (err) { toast(err.message, 'err'); }
})();
</script>
@endsection
