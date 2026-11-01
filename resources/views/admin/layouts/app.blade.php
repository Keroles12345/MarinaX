<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ __('النظام') }}</title>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
<div class="layout sidebar-collapsed">
  <aside class="sidebar">
    <div class="brand">
      <div><b>{{ __('النظام') }}</b></div>
    </div>
    <style>
      .sidebar{background:linear-gradient(180deg,#fff 0%,#f8fafc 100%);border-left-color:#e5eaf1}
      [dir="ltr"] .sidebar{border-left:0;border-right:1px solid #e5eaf1}
      .brand{min-height:76px;padding:18px 20px;border-bottom-color:#edf1f5}
      .brand b{font-size:16px;color:#172033;letter-spacing:-.02em}
      .nav{gap:7px;padding:16px 12px}
      .nav-category{display:block;padding:4px;border:1px solid transparent;border-radius:13px;transition:background .18s,border-color .18s}
      .nav-category[open]{background:#f8fafc;border-color:#edf1f5}
      .nav-category-title{display:flex;align-items:center;justify-content:space-between;gap:10px;min-height:40px;padding:9px 11px;color:#667085;font-size:12px;font-weight:800;letter-spacing:0;list-style:none;cursor:pointer;border-radius:9px;transition:background .18s,color .18s}
      .nav-category-title::-webkit-details-marker{display:none}
      .nav-category-title::after{content:'';width:7px;height:7px;border-right:1.5px solid currentColor;border-bottom:1.5px solid currentColor;transform:rotate(45deg) translateY(-2px);transition:transform .18s}
      .nav-category[open]>.nav-category-title::after{transform:rotate(225deg) translate(-1px,-1px)}
      .nav-category-title:hover,.nav-category[open]>.nav-category-title{background:#eef2f7;color:#344054}
      .nav-category-links{display:grid;gap:3px;padding:4px 0 3px}
      .sidebar .nav .nav-category-links a{min-height:39px;padding:8px 11px;border:1px solid transparent;border-radius:9px;color:#596579;font-size:13.5px;font-weight:600;transition:background .18s,color .18s,border-color .18s,transform .18s}
      .sidebar .nav .nav-category-links a .ic{width:23px;height:23px;display:grid;place-items:center;flex:0 0 23px;border-radius:7px;background:#eef2f7;font-size:14px}
      .sidebar .nav .nav-category-links a:hover{background:#fff;border-color:#e8edf3;color:#172033;transform:translateX(-2px)}
      .sidebar .nav .nav-category-links a.active{background:#fff8e8;border-color:#f1dfb6;color:#8a5b08;box-shadow:0 2px 5px rgba(16,24,40,.035)}
      .sidebar .nav .nav-category-links a.active .ic{background:#fcecc8}
      .sidebar .nav-logout{min-height:40px;margin-top:6px;border:1px solid transparent;color:#667085;font-size:13.5px}
      .sidebar .nav-logout:hover{background:#fff1f0;border-color:#fee4e2;color:#b42318}
      .side-footer{border-top-color:#edf1f5;color:#98a2b3}
      @media(max-width:900px){.nav-category{flex:1 0 100%}.nav-category-links{grid-template-columns:repeat(auto-fit,minmax(190px,1fr));align-items:start}}
    </style>

    <nav class="nav">
      <details class="nav-category">
        <summary class="nav-category-title">{{ __('الرئيسية') }}</summary>
        <div class="nav-category-links">
          <a href="/accounting" class="{{ request()->getPathInfo() === '/accounting' ? 'active' : '' }}"><span class="ic">🏠</span>{{ __('لوحة التحكم') }}</a>
        </div>
      </details>

      <details class="nav-category">
        <summary class="nav-category-title">{{ __('المحاسبة والتقارير') }}</summary>
        <div class="nav-category-links">
          <a href="/accounts" class="{{ request()->getPathInfo() === '/accounts' ? 'active' : '' }}"><span class="ic">📚</span>{{ __('دليل الحسابات') }}</a>
          <a href="/accounts/tree" class="{{ request()->getPathInfo() === '/accounts/tree' ? 'active' : '' }}"><span class="ic">🌳</span>{{ __('شجرة الحسابات') }}</a>
          <a href="/journal" class="{{ request()->getPathInfo() === '/journal' ? 'active' : '' }}"><span class="ic">📒</span>{{ __('دفتر اليومية') }}</a>
          <a href="/ledger" class="{{ request()->getPathInfo() === '/ledger' ? 'active' : '' }}"><span class="ic">📖</span>{{ __('الأستاذ العام') }}</a>
          <a href="/trial-balance" class="{{ request()->getPathInfo() === '/trial-balance' ? 'active' : '' }}"><span class="ic">⚖️</span>{{ __('ميزان المراجعة') }}</a>
          <a href="/budget" class="{{ request()->getPathInfo() === '/budget' ? 'active' : '' }}"><span class="ic">📊</span>{{ __('الموازنة التقديرية') }}</a>
          <a href="/income-statement" class="{{ request()->getPathInfo() === '/income-statement' ? 'active' : '' }}"><span class="ic">📈</span>{{ __('قائمة الدخل') }}</a>
          <a href="/balance-sheet" class="{{ request()->getPathInfo() === '/balance-sheet' ? 'active' : '' }}"><span class="ic">🏦</span>{{ __('المركز المالي') }}</a>
          <a href="/cash-flow" class="{{ request()->getPathInfo() === '/cash-flow' ? 'active' : '' }}"><span class="ic">💵</span>{{ __('التدفقات النقدية') }}</a>
          <a href="{{ route('boat-revenues.index') }}" class="{{ request()->is('boat-revenues') ? 'active' : '' }}"><span class="ic">⛵</span>{{ __('إيرادات المراكب') }}</a>
          <a href="{{ route('vehicle-revenues.index') }}" class="{{ request()->is('vehicle-revenues') ? 'active' : '' }}"><span class="ic">🚐</span>{{ __('إيرادات السيارات والأتوبيسات') }}</a>
          @if(in_array($user->role, ['admin', 'accountant'], true))
            <a href="{{ route('employee-custody.index') }}" class="{{ request()->is('employee-custody') ? 'active' : '' }}"><span class="ic">💼</span>{{ __('عهد الموظفين') }}</a>
          @endif
        </div>
      </details>

      @if($user->role === 'admin')
        <details class="nav-category">
          <summary class="nav-category-title">{{ __('إدارة الموارد') }}</summary>
          <div class="nav-category-links">
            <a href="{{ route('accounting.suppliers') }}" class="{{ request()->is('operation-resources/suppliers') ? 'active' : '' }}"><span class="ic">📦</span>{{ __('الموردون') }}</a>
            <a href="{{ route('accounting.companies') }}" class="{{ request()->is('operation-resources/companies') ? 'active' : '' }}"><span class="ic">🏢</span>{{ __('الشركات') }}</a>
            <a href="{{ route('accounting.company-prices') }}" class="{{ request()->is('operation-resources/company-prices') ? 'active' : '' }}"><span class="ic">💰</span>{{ __('أسعار الشركات') }}</a>
            <a href="{{ route('accounting.extras') }}" class="{{ request()->is('operation-resources/extras') ? 'active' : '' }}"><span class="ic">✨</span>{{ __('الخدمات الإضافية') }}</a>
            <a href="{{ route('accounting.trips') }}" class="{{ request()->is('operation-resources/trips') ? 'active' : '' }}"><span class="ic">🧭</span>{{ __('الرحلات') }}</a>
            <a href="{{ route('accounting.hotels') }}" class="{{ request()->is('operation-resources/hotels') ? 'active' : '' }}"><span class="ic">🏨</span>{{ __('الفنادق') }}</a>
            <a href="{{ route('accounting.boats') }}" class="{{ request()->is('operation-resources/boats') ? 'active' : '' }}"><span class="ic">⛵</span>{{ __('المراكب') }}</a>
            <a href="{{ route('accounting.vehicles') }}" class="{{ request()->is('operation-resources/vehicles') ? 'active' : '' }}"><span class="ic">🚐</span>{{ __('السيارات والأتوبيسات') }}</a>
            <a href="{{ route('accounting.drivers') }}" class="{{ request()->is('operation-resources/drivers*') ? 'active' : '' }}"><span class="ic">🚐</span>{{ __('سائقو السيارات والأتوبيسات') }}</a>
            <a href="{{ route('accounting.boat-drivers') }}" class="{{ request()->is('operation-resources/boat-drivers*') ? 'active' : '' }}"><span class="ic">⛵</span>{{ __('سائقو المراكب') }}</a>
            <a href="{{ route('accounting.regions') }}" class="{{ request()->is('regions/manage') ? 'active' : '' }}"><span class="ic">🗺️</span>{{ __('المناطق') }}</a>
            <a href="{{ route('accounting.trip-hotels') }}" class="{{ request()->is('trips/hotels') ? 'active' : '' }}"><span class="ic">🏨</span>{{ __('ربط الرحلات بالفنادق') }}</a>
          </div>
        </details>
        <details class="nav-category">
          <summary class="nav-category-title">{{ __('إدارة النظام') }}</summary>
          <div class="nav-category-links">
            <a href="/users" class="{{ request()->getPathInfo() === '/users' ? 'active' : '' }}"><span class="ic">👥</span>{{ __('المستخدمون') }}</a>
            <a href="{{ route('admin.user-permissions') }}" class="{{ request()->routeIs('admin.user-permissions*') ? 'active' : '' }}"><span class="ic">🔐</span>{{ __('صلاحيات المستخدمين') }}</a>
            <a href="/audit" class="{{ request()->getPathInfo() === '/audit' ? 'active' : '' }}"><span class="ic">🛡️</span>{{ __('سجل العمليات') }}</a>
          </div>
        </details>
      @endif

      <details class="nav-category">
        <summary class="nav-category-title">{{ __('المساعدة والإعدادات') }}</summary>
        <div class="nav-category-links">
          <a href="/help" class="{{ request()->getPathInfo() === '/help' ? 'active' : '' }}"><span class="ic">📘</span>{{ __('دليل الاستخدام') }}</a>
          <a href="/settings" class="{{ request()->getPathInfo() === '/settings' ? 'active' : '' }}"><span class="ic">⚙️</span>{{ __('الإعدادات') }}</a>
        </div>
      </details>
      <form method="post" action="{{ route('accounting.logout') }}">@csrf<button class="nav-logout" type="submit"><span class="ic">🚪</span>{{ __('تسجيل الخروج') }}</button></form>
    </nav>
    <div class="side-footer">© {{ __('جميع الحقوق محفوظة') }}<br></div>
  </aside>

  <main class="main">
    <header class="topbar">
      <button id="sidebar-toggle" class="sidebar-toggle" type="button" aria-label="{{ __('إظهار القائمة الجانبية') }}" aria-expanded="false" title="{{ __('إظهار القائمة الجانبية') }}">
        <svg class="sidebar-toggle-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>
      <h2>{{ __(trim($__env->yieldContent('page_title'))) }}</h2>
      @include('shared.language-switcher')
      <div class="nav-page-search">

        <div class="nav-page-search-control">
          <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
            <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.8"/>
            <path d="m16 16 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
          <input id="nav-page-search-input" type="search" autocomplete="off"
            role="combobox" aria-autocomplete="list" aria-expanded="false"
            aria-controls="nav-page-search-results"
            placeholder="{{ __('ابحث عن صفحة...') }}">
        </div>
        <div id="nav-page-search-results" class="nav-page-search-results" role="listbox" hidden></div>
      </div>
      <div class="user-chip">
        <div class="avatar">{{ mb_substr($user->username, 0, 1) }}</div>
        <div><span class="username" dir="auto">{{ $user->username ?? $user->email }}</span><span class="role">{{ $user->name }} · {{ __($roleName) }}@if($user->tenant) · {{ $user->tenant->name }}@endif</span></div>
      </div>
    </header>
    <div class="content">@yield('content')</div>
    <footer class="footer-note">© {{ __('جميع الحقوق محفوظة') }}</footer>
  </main>
</div>
<style>
  .topbar{min-height:72px;gap:18px;padding:12px 24px;background:linear-gradient(110deg,rgba(255,255,255,.98),rgba(248,250,252,.96));border-bottom-color:#e8edf3;box-shadow:0 3px 14px rgba(16,24,40,.045)}
  .sidebar-toggle{width:42px;height:42px;flex-basis:42px;border-color:#e3e8ef;border-radius:12px;background:#fff;box-shadow:0 1px 3px rgba(16,24,40,.04)}
  .topbar h2{flex:0 1 auto;min-width:130px;max-width:260px;padding-inline-start:13px;border-inline-start:3px solid #d6ad55;color:#1d2939;font-size:19px;line-height:1.35;letter-spacing:-.025em}
  .user-chip{flex:0 0 auto;max-width:280px;min-height:46px;padding:6px 12px;background:rgba(255,255,255,.9);border-color:#e8edf3;box-shadow:0 2px 6px rgba(16,24,40,.045)}
  .user-chip .avatar{width:32px;height:32px;flex:0 0 32px}
  .user-chip .username{color:#344054;font-weight:700}
  .user-chip>div:last-child{min-width:0}
  .user-chip .username,.user-chip .role{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .nav-page-search{position:relative;flex:1 1 320px;max-width:520px;min-width:180px;margin-inline:auto;z-index:45}
  .nav-page-search-control{display:flex;align-items:center;gap:10px;min-height:44px;padding:0 14px;border:1px solid #dce3ec;border-radius:12px;background:rgba(255,255,255,.78);box-shadow:inset 0 1px 2px rgba(16,24,40,.025);transition:background .18s,border-color .18s,box-shadow .18s}
  .nav-page-search-control>svg{width:17px;height:17px;flex:0 0 17px;color:#98a2b3}
  .nav-page-search-control:focus-within{border-color:#d6ad55;background:#fff;box-shadow:0 0 0 3px rgba(212,167,44,.13),0 3px 8px rgba(16,24,40,.04)}
  .nav-page-search-control input{width:100%;min-width:0;min-height:38px;padding:0;border:0;outline:0;background:transparent;color:#344054;font:inherit;font-size:13px;box-shadow:none}
  .nav-page-search-control input:focus{border:0;box-shadow:none}
  .nav-page-search-control input::placeholder{color:#98a2b3}
  .nav-page-search-results{position:absolute;top:calc(100% + 8px);inset-inline:0;max-height:min(420px,65vh);overflow:auto;padding:6px;border:1px solid #e4e7ec;border-radius:12px;background:#fff;box-shadow:0 16px 38px rgba(16,24,40,.15)}
  .nav-page-search-results a{display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:40px;padding:9px 11px;border-radius:8px;color:#344054;text-decoration:none;transition:background .15s,color .15s}
  .nav-page-search-results a:hover,.nav-page-search-results a[aria-selected="true"]{background:#fff8e8;color:#8a5b08}
  .nav-page-search-results a span{font-size:13px;font-weight:600}
  .nav-page-search-results small{color:#98a2b3;direction:ltr;font-size:11px}
  .nav-page-search-results .hint{padding:12px;color:#667085;font-size:13px}
  @media(max-width:850px){.topbar{gap:12px;padding-inline:16px}.topbar h2{min-width:0;max-width:220px;font-size:17px}.nav-page-search{flex-basis:240px}}
  @media(max-width:700px){.topbar{flex-wrap:wrap;gap:10px;padding:12px 16px}.topbar h2{flex:1;max-width:none}.nav-page-search{order:3;flex:1 0 100%;max-width:none;margin:0}.user-chip{margin-inline-start:auto}}
  @media(max-width:420px){.topbar{padding:10px 12px}.topbar h2{font-size:16px}.user-chip{max-width:calc(100% - 54px);min-height:42px;padding:5px 9px;font-size:12px}.user-chip .role{font-size:10px}}
</style>
@include('shared.dashboard-translations')
<script src="{{ asset('js/app.js') }}"></script>
<script>
(() => {
  const input = document.getElementById('nav-page-search-input');
  const results = document.getElementById('nav-page-search-results');
  const storageKey = 'admin-page-search-query';
  const links = [...document.querySelectorAll('.sidebar .nav a[href]')]
    .map(link => ({ label: link.textContent.trim(), href: link.href }))
    .filter((item, index, all) => item.label && new URL(item.href).origin === location.origin
      && all.findIndex(candidate => candidate.href === item.href) === index);
  let selectedIndex = -1;

  input.value = sessionStorage.getItem(storageKey) || '';

  function closeResults() {
    results.hidden = true;
    input.setAttribute('aria-expanded', 'false');
    input.removeAttribute('aria-activedescendant');
    selectedIndex = -1;
  }

  function renderResults(showAll = false) {
    const query = showAll ? '' : input.value.trim().toLocaleLowerCase();
    results.replaceChildren();
    selectedIndex = -1;

    const matches = (query
      ? links
        .filter(item => item.label.toLocaleLowerCase().includes(query))
        .sort((a, b) => Number(!a.label.toLocaleLowerCase().startsWith(query))
          - Number(!b.label.toLocaleLowerCase().startsWith(query)))
      : links).slice(0, 8);

    if (!matches.length) {
      const empty = document.createElement('div');
      empty.className = 'hint';
      empty.textContent = @json(__('لا توجد صفحة مطابقة'));
      results.append(empty);
    } else {
      matches.forEach((item, index) => {
        const link = document.createElement('a');
        link.href = item.href;
        link.id = `nav-page-result-${index}`;
        link.setAttribute('role', 'option');
        link.setAttribute('aria-selected', 'false');
        const label = document.createElement('span');
        label.textContent = item.label;
        const path = document.createElement('small');
        path.textContent = new URL(item.href).pathname;
        link.append(label, path);
        link.addEventListener('click', () => {
          sessionStorage.setItem(storageKey, item.label);
        });
        results.append(link);
      });
    }
    results.hidden = false;
    input.setAttribute('aria-expanded', 'true');
  }

  function selectResult(index) {
    const options = [...results.querySelectorAll('[role="option"]')];
    if (!options.length) return;
    selectedIndex = (index + options.length) % options.length;
    options.forEach((option, optionIndex) => {
      option.setAttribute('aria-selected', String(optionIndex === selectedIndex));
    });
    input.setAttribute('aria-activedescendant', options[selectedIndex].id);
  }

  input.addEventListener('input', () => {
    const query = input.value.trim();
    if (query) {
      sessionStorage.setItem(storageKey, query);
    } else {
      sessionStorage.removeItem(storageKey);
    }
    renderResults();
  });
  input.addEventListener('focus', () => renderResults(true));
  input.addEventListener('keydown', event => {
    if (results.hidden) return;
    const options = results.querySelectorAll('[role="option"]');
    if (event.key === 'ArrowDown' && options.length) {
      event.preventDefault();
      selectResult(selectedIndex + 1);
    } else if (event.key === 'ArrowUp' && options.length) {
      event.preventDefault();
      selectResult(selectedIndex < 0 ? options.length - 1 : selectedIndex - 1);
    } else if (event.key === 'Enter' && selectedIndex >= 0 && options[selectedIndex]) {
      event.preventDefault();
      sessionStorage.setItem(storageKey, options[selectedIndex].querySelector('span').textContent);
      window.location.href = options[selectedIndex].href;
    } else if (event.key === 'Escape') {
      closeResults();
    }
  });
  document.addEventListener('click', event => {
    if (!event.target.closest('.nav-page-search')) closeResults();
  });
})();
</script>
<script src="{{ asset('js/infinite-scroll.js') }}"></script>
@yield('scripts')
</body>
</html>
