<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __(trim($__env->yieldContent('title', 'لوحة سوبر فايزر'))) }}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        .superadmin-layout{display:flex;min-height:100vh;font-family:'Tajawal',sans-serif}
        .superadmin-sidebar{position:sticky;top:0;display:flex;width:250px;height:100vh;flex:0 0 250px;flex-direction:column;overflow-y:auto;border-left:1px solid #e5eaf1;background:linear-gradient(180deg,#fff 0%,#f8fafc 100%);transition:width .25s ease,flex-basis .25s ease,border-width .25s ease}
        [dir="ltr"] .superadmin-sidebar{border-left:0;border-right:1px solid #e5eaf1}
        .superadmin-layout.sidebar-collapsed .superadmin-sidebar{width:0;flex-basis:0;overflow:hidden;border-width:0}
        .superadmin-brand{min-height:76px;padding:18px 20px;border-bottom:1px solid #edf1f5;color:#172033;font-size:16px;font-weight:800}
        .superadmin-nav{display:flex;flex:1;flex-direction:column;gap:7px;padding:16px 12px}
        .superadmin-nav-category{display:block;padding:4px;border:1px solid transparent;border-radius:13px;transition:background .18s,border-color .18s}
        .superadmin-nav-category[open]{border-color:#edf1f5;background:#f8fafc}
        .superadmin-nav-title{display:flex;min-height:40px;align-items:center;justify-content:space-between;gap:10px;padding:9px 11px;border-radius:9px;color:#667085;font-size:12px;font-weight:800;list-style:none;cursor:pointer}
        .superadmin-nav-title::-webkit-details-marker{display:none}
        .superadmin-nav-title:after{width:7px;height:7px;transform:rotate(45deg) translateY(-2px);border-right:1.5px solid currentColor;border-bottom:1.5px solid currentColor;content:''}
        .superadmin-nav-category[open]>.superadmin-nav-title:after{transform:rotate(225deg) translate(-1px,-1px)}
        .superadmin-nav-title:hover,.superadmin-nav-category[open]>.superadmin-nav-title{background:#eef2f7;color:#344054}
        .superadmin-nav-links{display:grid;gap:3px;padding:4px 0 3px}
        .superadmin-nav a,.superadmin-logout{display:flex;min-height:39px;align-items:center;gap:11px;padding:8px 11px;border:1px solid transparent;border-radius:9px;background:transparent;color:#596579;text-align:right;text-decoration:none;font-size:13.5px;font-weight:600;transition:background .18s,color .18s,border-color .18s,transform .18s;cursor:pointer}
        [dir="ltr"] .superadmin-nav a,[dir="ltr"] .superadmin-logout{text-align:left}
        .superadmin-nav a:hover,.superadmin-logout:hover{transform:translateX(-2px);border-color:#e8edf3;background:#fff;color:#172033}
        .superadmin-nav a.active{border-color:#f1dfb6;background:#fff8e8;color:#8a5b08;box-shadow:0 2px 5px rgba(16,24,40,.035)}
        .superadmin-nav-icon{display:grid;width:23px;height:23px;flex:0 0 23px;place-items:center;border-radius:7px;background:#eef2f7;font-size:14px}
        .superadmin-nav a.active .superadmin-nav-icon{background:#fcecc8}
        .superadmin-nav form{margin-top:auto}
        .superadmin-logout{width:100%;margin-top:6px}
        .superadmin-logout:hover{border-color:#fee4e2;background:#fff1f0;color:#b42318}
        .superadmin-sidebar-footer{padding:14px 16px;border-top:1px solid #edf1f5;color:#98a2b3;text-align:center;font-size:12px}
        .superadmin-main{display:flex;min-width:0;flex:1;flex-direction:column}
        .superadmin-topbar{position:sticky;top:0;z-index:40;display:flex;min-height:72px;align-items:center;gap:18px;padding:12px 24px;border-bottom:1px solid #e8edf3;background:linear-gradient(110deg,rgba(255,255,255,.98),rgba(248,250,252,.96));box-shadow:0 3px 14px rgba(16,24,40,.045);backdrop-filter:blur(8px)}
        .superadmin-topbar h1{min-width:130px;max-width:260px;flex:0 1 auto;margin:0;padding-inline-start:13px;border-inline-start:3px solid #d6ad55;color:#1d2939;font-size:19px;font-weight:800;line-height:1.35}
        .superadmin-menu-toggle{display:inline-flex;width:42px;height:42px;flex:0 0 42px;align-items:center;justify-content:center;border:1px solid #e3e8ef;border-radius:12px;background:#fff;color:#334155;box-shadow:0 1px 3px rgba(16,24,40,.04);cursor:pointer}
        .superadmin-menu-toggle:hover{border-color:#d6ad55;background:#fff8e8;color:#8a5b08}
        .superadmin-menu-toggle svg{width:20px;height:20px}
        .superadmin-search{position:relative;z-index:45;min-width:180px;max-width:520px;flex:1 1 320px;margin-inline:auto}
        .superadmin-search-control{display:flex;min-height:44px;align-items:center;gap:10px;padding:0 14px;border:1px solid #dce3ec;border-radius:12px;background:rgba(255,255,255,.78);box-shadow:inset 0 1px 2px rgba(16,24,40,.025)}
        .superadmin-search-control svg{width:17px;height:17px;flex:0 0 17px;color:#98a2b3}
        .superadmin-search-control:focus-within{border-color:#d6ad55;background:#fff;box-shadow:0 0 0 3px rgba(212,167,44,.13),0 3px 8px rgba(16,24,40,.04)}
        .superadmin-search-control input{width:100%;min-width:0;min-height:38px;padding:0;border:0;outline:0;background:transparent;color:#344054;font:inherit;font-size:13px;box-shadow:none}
        .superadmin-search-results{position:absolute;top:calc(100% + 8px);inset-inline:0;max-height:min(420px,65vh);overflow:auto;padding:6px;border:1px solid #e4e7ec;border-radius:12px;background:#fff;box-shadow:0 16px 38px rgba(16,24,40,.15)}
        .superadmin-search-results a{display:flex;min-height:40px;align-items:center;justify-content:space-between;gap:12px;padding:9px 11px;border-radius:8px;color:#344054;text-decoration:none}
        .superadmin-search-results a:hover,.superadmin-search-results a[aria-selected="true"]{background:#fff8e8;color:#8a5b08}
        .superadmin-search-results a span{font-size:13px;font-weight:600}
        .superadmin-search-results small{color:#98a2b3;direction:ltr;font-size:11px}
        .superadmin-search-empty{padding:12px;color:#667085;font-size:13px}
        .superadmin-user-chip{display:flex;min-height:46px;max-width:280px;flex:0 0 auto;align-items:center;gap:10px;padding:6px 12px;border:1px solid #e8edf3;border-radius:999px;background:rgba(255,255,255,.9);font-size:13px;box-shadow:0 2px 6px rgba(16,24,40,.045)}
        .superadmin-user-avatar{display:flex;width:32px;height:32px;flex:0 0 32px;align-items:center;justify-content:center;border-radius:50%;background:linear-gradient(145deg,#2dd4bf,#178777);color:#04201c;font-size:14px;font-weight:800}
        .superadmin-user-name,.superadmin-user-role{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .superadmin-user-name{max-width:200px;color:#344054;font-weight:700}
        .superadmin-user-role{color:#667085;font-size:11px}
        .superadmin-content{min-width:0;flex:1;padding:26px}
        .superadmin-footer{padding:18px;border-top:1px solid #e2e8f0;color:#98a2b3;text-align:center;font-size:12.5px}
        @media(max-width:900px){.superadmin-layout{flex-direction:column}.superadmin-sidebar{position:static;width:100%;height:auto;flex:0 0 auto;transition:none}.superadmin-layout.sidebar-collapsed .superadmin-sidebar{display:none}.superadmin-nav{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}.superadmin-nav-category{min-width:0}.superadmin-topbar{gap:12px;padding:12px 16px}.superadmin-search{flex-basis:240px}.superadmin-content{padding:18px}}
        @media(max-width:700px){.superadmin-topbar{flex-wrap:wrap;gap:10px}.superadmin-topbar h1{max-width:none;flex:1}.superadmin-search{order:3;max-width:none;flex:1 0 100%;margin:0}.superadmin-user-chip{margin-inline-start:auto}}
        @media(max-width:600px){.superadmin-topbar{gap:9px;padding:10px 12px}.superadmin-topbar h1{font-size:16px}.superadmin-user-chip{padding:4px 7px}.superadmin-user-name{max-width:110px}.superadmin-content{padding:12px}.superadmin-nav{grid-template-columns:1fr}}
        @media(prefers-reduced-motion:reduce){.superadmin-sidebar{transition:none}}
    </style>
</head>
<body>
    @php($superAdmin = auth('superadmin')->user())
    <div class="superadmin-layout sidebar-collapsed">
        <aside class="superadmin-sidebar">
            <div class="superadmin-brand">{{ __('النظام') }}</div>
            <nav class="superadmin-nav">
                <details class="superadmin-nav-category" open>
                    <summary class="superadmin-nav-title">{{ __('إدارة المنصة') }}</summary>
                    <div class="superadmin-nav-links">
                        <a href="{{ route('superadmin.dashboard') }}" class="{{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}"><span class="superadmin-nav-icon">🏠</span>{{ __('لوحة التحكم') }}</a>
                        <a href="{{ route('superadmin.tenants.index') }}" class="{{ request()->routeIs('superadmin.tenants.index') ? 'active' : '' }}"><span class="superadmin-nav-icon">🏢</span>{{ __('إدارة الشركات') }}</a>
                        <a href="{{ route('superadmin.tenants.create') }}" class="{{ request()->routeIs('superadmin.tenants.create') ? 'active' : '' }}"><span class="superadmin-nav-icon">➕</span>{{ __('إضافة شركة') }}</a>
                        <a href="{{ route('superadmin.users.create') }}" class="{{ request()->routeIs('superadmin.users.create') ? 'active' : '' }}"><span class="superadmin-nav-icon">👤</span>{{ __('إضافة مستخدم') }}</a>
                        <a href="{{ route('superadmin.users.index') }}" class="{{ request()->routeIs('superadmin.users.index') ? 'active' : '' }}"><span class="superadmin-nav-icon">👥</span>{{ __('إدارة المستخدمين') }}</a>
                        <a href="{{ route('superadmin.permissions.index') }}" class="{{ request()->routeIs('superadmin.permissions.*') ? 'active' : '' }}"><span class="superadmin-nav-icon">🔐</span>{{ __('صلاحيات المستخدمين') }}</a>
                    </div>
                </details>
                <form method="post" action="{{ route('superadmin.logout') }}">
                    @csrf
                    <button class="superadmin-logout" type="submit"><span class="superadmin-nav-icon">🚪</span>{{ __('تسجيل الخروج') }}</button>
                </form>
            </nav>
            <div class="superadmin-sidebar-footer">© {{ __('جميع الحقوق محفوظة') }}</div>
        </aside>
        <main class="superadmin-main">
            <header class="superadmin-topbar">
                <button id="superadmin-menu-toggle" class="superadmin-menu-toggle" type="button" aria-label="{{ __('إظهار القائمة الجانبية') }}" aria-expanded="false" title="{{ __('إظهار القائمة الجانبية') }}">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
                <h1>{{ __(trim($__env->yieldContent('page-title', 'لوحة التحكم'))) }}</h1>
                @include('shared.language-switcher')
                <div class="superadmin-search">
                    <div class="superadmin-search-control">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        <input id="superadmin-search-input" type="search" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="superadmin-search-results" placeholder="{{ __('ابحث عن صفحة...') }}">
                    </div>
                    <div id="superadmin-search-results" class="superadmin-search-results" role="listbox" hidden></div>
                </div>
                <div class="superadmin-user-chip">
                    <span class="superadmin-user-avatar">{{ mb_substr($superAdmin->name ?? $superAdmin->email, 0, 1) }}</span>
                    <div><span class="superadmin-user-name" dir="auto">{{ $superAdmin->name ?? $superAdmin->email }}</span><span class="superadmin-user-role">{{ __('سوبر فايزر') }}</span></div>
                </div>
            </header>
            <div class="superadmin-content">@yield('body')</div>
            <footer class="superadmin-footer">© {{ date('Y') }} {{ __('جميع الحقوق محفوظة') }}</footer>
        </main>
    </div>
    @include('shared.dashboard-translations')
    <script src="{{ asset('js/app.js') }}"></script>
    <script>
        (() => {
            const layout = document.querySelector('.superadmin-layout');
            const toggle = document.getElementById('superadmin-menu-toggle');
            const stateKey = 'superadmin-sidebar-state';
            if (!layout || !toggle) return;

            if (localStorage.getItem(stateKey) === 'open') layout.classList.remove('sidebar-collapsed');
            const updateToggle = () => {
                const collapsed = layout.classList.contains('sidebar-collapsed');
                const label = collapsed ? @json(__('إظهار القائمة الجانبية')) : @json(__('إخفاء القائمة الجانبية'));
                toggle.setAttribute('aria-expanded', String(!collapsed));
                toggle.setAttribute('aria-label', label);
                toggle.title = label;
            };
            updateToggle();
            toggle.addEventListener('click', () => {
                const collapsed = layout.classList.toggle('sidebar-collapsed');
                localStorage.setItem(stateKey, collapsed ? 'closed' : 'open');
                updateToggle();
            });

            const input = document.getElementById('superadmin-search-input');
            const results = document.getElementById('superadmin-search-results');
            const links = [...document.querySelectorAll('.superadmin-nav a[href]')];
            let selectedIndex = -1;
            const closeResults = () => {
                results.hidden = true;
                input.setAttribute('aria-expanded', 'false');
                input.removeAttribute('aria-activedescendant');
                selectedIndex = -1;
            };
            const renderResults = () => {
                const query = input.value.trim().toLocaleLowerCase();
                results.replaceChildren();
                selectedIndex = -1;
                const matches = links.filter(link => link.textContent.trim().toLocaleLowerCase().includes(query)).slice(0, 8);
                if (!matches.length) {
                    const empty = document.createElement('div');
                    empty.className = 'superadmin-search-empty';
                    empty.textContent = @json(__('لا توجد صفحة مطابقة'));
                    results.append(empty);
                } else {
                    matches.forEach((item, index) => {
                        const link = document.createElement('a');
                        link.href = item.href;
                        link.id = `superadmin-search-result-${index}`;
                        link.setAttribute('role', 'option');
                        link.setAttribute('aria-selected', 'false');
                        const label = document.createElement('span');
                        label.textContent = item.textContent.trim();
                        const path = document.createElement('small');
                        path.textContent = new URL(item.href).pathname;
                        link.append(label, path);
                        results.append(link);
                    });
                }
                results.hidden = false;
                input.setAttribute('aria-expanded', 'true');
            };
            input.addEventListener('input', renderResults);
            input.addEventListener('focus', renderResults);
            input.addEventListener('keydown', event => {
                const options = [...results.querySelectorAll('[role="option"]')];
                if (event.key === 'Escape') closeResults();
                if ((event.key === 'ArrowDown' || event.key === 'ArrowUp') && options.length) {
                    event.preventDefault();
                    selectedIndex = (selectedIndex + (event.key === 'ArrowDown' ? 1 : -1) + options.length) % options.length;
                    options.forEach((option, index) => option.setAttribute('aria-selected', String(index === selectedIndex)));
                    input.setAttribute('aria-activedescendant', options[selectedIndex].id);
                } else if (event.key === 'Enter' && selectedIndex >= 0 && options[selectedIndex]) {
                    event.preventDefault();
                    window.location.href = options[selectedIndex].href;
                }
            });
            document.addEventListener('click', event => {
                if (!event.target.closest('.superadmin-search')) closeResults();
            });
        })();
    </script>
    @yield('scripts')
</body>
</html>
