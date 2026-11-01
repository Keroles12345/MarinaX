<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    @include('staff.layout.head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        .staff-layout{display:flex;min-height:100vh}
        .staff-layout:not(.staff-dashboard-layout){background:#f5f7fa;color:#1f2937}
        .staff-sidebar{width:250px;flex:0 0 250px;min-width:0;position:sticky;top:0;display:flex;height:100vh;flex-direction:column;overflow-y:auto;background:linear-gradient(180deg,#fff 0%,#f8fafc 100%);border-left:1px solid #e5eaf1;transition:width .25s ease,flex-basis .25s ease,border-width .25s ease}
        [dir="ltr"] .staff-sidebar{border-left:0;border-right:1px solid #e5eaf1}
        .staff-layout.sidebar-collapsed .staff-sidebar{width:0;flex-basis:0;border-width:0;overflow:hidden}
        .staff-brand{min-height:76px;padding:18px 20px;border-bottom:1px solid #edf1f5;color:#172033;font-size:16px;font-weight:800}
        .staff-nav{display:flex;flex:1;flex-direction:column;gap:7px;padding:16px 12px}
        .staff-nav-category{display:block;padding:4px;border:1px solid transparent;border-radius:13px;transition:background .18s,border-color .18s}
        .staff-nav-category[open]{background:#f8fafc;border-color:#edf1f5}
        .staff-nav-title{display:flex;align-items:center;justify-content:space-between;gap:10px;min-height:40px;padding:9px 11px;border-radius:9px;color:#667085;font-size:12px;font-weight:800;list-style:none;cursor:pointer}
        .staff-nav-title::-webkit-details-marker{display:none}
        .staff-nav-title::after{content:'';width:7px;height:7px;border-right:1.5px solid currentColor;border-bottom:1.5px solid currentColor;transform:rotate(45deg) translateY(-2px)}
        .staff-nav-category[open]>.staff-nav-title::after{transform:rotate(225deg) translate(-1px,-1px)}
        .staff-nav-title:hover,.staff-nav-category[open]>.staff-nav-title{background:#eef2f7;color:#344054}
        .staff-nav-links{display:grid;gap:3px;padding:4px 0 3px}
        .staff-nav a,.staff-logout{display:flex;align-items:center;gap:11px;min-height:39px;padding:8px 11px;border:1px solid transparent;border-radius:9px;background:transparent;color:#596579;text-align:right;text-decoration:none;font-size:13.5px;font-weight:600;transition:background .18s,color .18s,border-color .18s,transform .18s;cursor:pointer}
        [dir="ltr"] .staff-nav a,[dir="ltr"] .staff-logout{text-align:left}
        .staff-nav a:hover,.staff-logout:hover{background:#fff;border-color:#e8edf3;color:#172033;transform:translateX(-2px)}
        .staff-nav a.active{border-color:#f1dfb6;background:#fff8e8;color:#8a5b08;box-shadow:0 2px 5px rgba(16,24,40,.035)}
        .staff-nav-icon{display:grid;width:23px;height:23px;flex:0 0 23px;place-items:center;border-radius:7px;background:#eef2f7;text-align:center;font-size:14px}
        .staff-nav a.active .staff-nav-icon{background:#fcecc8}
        .staff-nav form{margin-top:auto}
        .staff-logout{width:100%;margin-top:6px}
        .staff-logout:hover{border-color:#fee4e2;background:#fff1f0;color:#b42318}
        .staff-sidebar-footer{padding:14px 16px;border-top:1px solid #edf1f5;color:#98a2b3;text-align:center;font-size:12px}
        .staff-main{display:flex;min-width:0;flex:1;flex-direction:column}
        .staff-topbar{position:sticky;top:0;z-index:40;display:flex;align-items:center;gap:18px;min-height:72px;border-bottom:1px solid #e8edf3;background:linear-gradient(110deg,rgba(255,255,255,.98),rgba(248,250,252,.96));padding:12px 24px;box-shadow:0 3px 14px rgba(16,24,40,.045);backdrop-filter:blur(8px)}
        .staff-topbar h1{min-width:130px;max-width:260px;flex:0 1 auto;margin:0;padding-inline-start:13px;border-inline-start:3px solid #d6ad55;color:#1d2939;font-size:19px;font-weight:800;line-height:1.35}
        .staff-sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
        .staff-page-search{position:relative;z-index:45;min-width:180px;max-width:520px;flex:1 1 320px;margin-inline:auto}
        .staff-page-search-control{display:flex;align-items:center;gap:10px;min-height:44px;padding:0 14px;border:1px solid #dce3ec;border-radius:12px;background:rgba(255,255,255,.78);box-shadow:inset 0 1px 2px rgba(16,24,40,.025);transition:background .18s,border-color .18s,box-shadow .18s}
        .staff-page-search-control>svg{width:17px;height:17px;flex:0 0 17px;color:#98a2b3}
        .staff-page-search-control:focus-within{border-color:#d6ad55;background:#fff;box-shadow:0 0 0 3px rgba(212,167,44,.13),0 3px 8px rgba(16,24,40,.04)}
        .staff-page-search-control input{width:100%;min-width:0;min-height:38px;padding:0;border:0;outline:0;background:transparent;color:#344054;font:inherit;font-size:13px;box-shadow:none}
        .staff-page-search-control input:focus{border:0;box-shadow:none}
        .staff-page-search-control input::placeholder{color:#98a2b3}
        .staff-page-search-results{position:absolute;top:calc(100% + 8px);inset-inline:0;max-height:min(420px,65vh);overflow:auto;padding:6px;border:1px solid #e4e7ec;border-radius:12px;background:#fff;box-shadow:0 16px 38px rgba(16,24,40,.15)}
        .staff-page-search-results a{display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:40px;padding:9px 11px;border-radius:8px;color:#344054;text-decoration:none;transition:background .15s,color .15s}
        .staff-page-search-results a:hover,.staff-page-search-results a[aria-selected="true"]{background:#fff8e8;color:#8a5b08}
        .staff-page-search-results a span{font-size:13px;font-weight:600}
        .staff-page-search-results small{color:#98a2b3;direction:ltr;font-size:11px}
        .staff-page-search-results .staff-search-empty{padding:12px;color:#667085;font-size:13px}
        .staff-menu-toggle{display:inline-flex;width:42px;height:42px;flex:0 0 42px;align-items:center;justify-content:center;border:1px solid #e3e8ef;border-radius:12px;background:#fff;color:#334155;box-shadow:0 1px 3px rgba(16,24,40,.04);cursor:pointer}
        .staff-menu-toggle:hover{border-color:#d6ad55;background:#fff8e8;color:#8a5b08}
        .staff-menu-toggle:focus-visible{outline:3px solid rgba(217,164,65,.4);outline-offset:2px}
        .staff-menu-toggle svg{width:20px;height:20px}
        .staff-user-chip{display:flex;align-items:center;gap:10px;max-width:280px;min-height:46px;border:1px solid #e8edf3;border-radius:999px;background:rgba(255,255,255,.9);padding:6px 12px;font-size:13px;box-shadow:0 2px 6px rgba(16,24,40,.045)}
        .staff-user-avatar{display:flex;width:32px;height:32px;flex:0 0 32px;align-items:center;justify-content:center;border-radius:50%;background:linear-gradient(145deg,#2dd4bf,#178777);color:#04201c;font-size:14px;font-weight:800}
        .staff-user-name{display:block;max-width:200px;overflow:hidden;color:#344054;font-weight:700;text-overflow:ellipsis;white-space:nowrap}
        .staff-user-role{display:block;overflow:hidden;color:#667085;font-size:11px;text-overflow:ellipsis;white-space:nowrap}
        .staff-content{min-width:0;flex:1;padding:26px}
        .staff-footer{border-top:1px solid #e2e8f0;padding:18px;color:#98a2b3;text-align:center;font-size:12.5px}
        .staff-dashboard-layout .staff-footer{border-top-color:#253355;color:#93a3c0}
        @media(max-width:900px){.staff-layout{flex-direction:column}.staff-sidebar{position:static;width:100%;height:auto;flex:0 0 auto;transition:none}.staff-layout.sidebar-collapsed .staff-sidebar{display:none}.staff-nav{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}.staff-nav-category{min-width:0}.staff-topbar{gap:12px;padding:12px 16px}.staff-page-search{flex-basis:240px}.staff-content{padding:18px}}
        @media(max-width:700px){.staff-topbar{flex-wrap:wrap;gap:10px}.staff-topbar h1{flex:1;max-width:none}.staff-page-search{order:3;flex:1 0 100%;max-width:none;margin:0}.staff-user-chip{margin-inline-start:auto}}
        @media(max-width:600px){.staff-topbar{gap:9px;padding:10px 12px}.staff-topbar h1{font-size:16px}.staff-user-chip{padding:4px 7px}.staff-user-name{max-width:110px}.staff-content{padding:12px}.staff-nav{grid-template-columns:1fr}}
        @media(prefers-reduced-motion:reduce){.staff-sidebar{transition:none}}
    </style>
</head>
<body>
    <div class="staff-layout {{ request()->routeIs('dashboard') ? 'staff-dashboard-layout' : '' }} sidebar-collapsed">
        @include('staff.layout.sidebar')

        <div class="staff-main">
            @include('staff.layout.navbar')
            <div class="staff-content">
                @include('staff.layout.alert')
                @yield('body')
            </div>
            <footer class="staff-footer">&copy; {{ date('Y') }} {{ __('جميع الحقوق محفوظة') }}</footer>
        </div>
    </div>

    @include('shared.dashboard-translations')
    <script src="{{ asset('js/app.js') }}"></script>
    <script>
        (() => {
            const layout = document.querySelector('.staff-layout');
            const toggle = document.getElementById('staff-sidebar-toggle');
            if (!layout || !toggle) return;

            const stateKey = 'admin-sidebar-state';
            const savedState = localStorage.getItem(stateKey);
            if (savedState === 'open') layout.classList.remove('sidebar-collapsed');

            const updateToggle = () => {
                const isCollapsed = layout.classList.contains('sidebar-collapsed');
                const label = isCollapsed ? @json(__('إظهار القائمة الجانبية')) : @json(__('إخفاء القائمة الجانبية'));
                toggle.setAttribute('aria-expanded', String(!isCollapsed));
                toggle.setAttribute('aria-label', label);
                toggle.title = label;
            };

            updateToggle();
            toggle.addEventListener('click', () => {
                const isCollapsed = layout.classList.toggle('sidebar-collapsed');
                localStorage.setItem(stateKey, isCollapsed ? 'closed' : 'open');
                updateToggle();
            });

            document.querySelectorAll('.staff-nav-category').forEach(category => {
                const title = category.querySelector('summary')?.textContent.trim();
                if (!title) return;
                const categoryKey = `admin-nav-category:${title}`;
                const savedCategoryState = localStorage.getItem(categoryKey);
                if (savedCategoryState !== null) category.open = savedCategoryState === 'open';
                category.addEventListener('toggle', () => {
                    localStorage.setItem(categoryKey, category.open ? 'open' : 'closed');
                });
            });
        })();

        (() => {
            const input = document.getElementById('staff-page-search-input');
            const results = document.getElementById('staff-page-search-results');
            if (!input || !results) return;

            const storageKey = 'staff-page-search-query';
            const links = [...document.querySelectorAll('.staff-nav a[href]')]
                .map(link => ({ label: link.textContent.trim(), href: link.href }))
                .filter((item, index, all) => item.label && new URL(item.href).origin === location.origin
                    && all.findIndex(candidate => candidate.href === item.href) === index);
            let selectedIndex = -1;

            input.value = sessionStorage.getItem(storageKey) || '';

            const closeResults = () => {
                results.hidden = true;
                input.setAttribute('aria-expanded', 'false');
                input.removeAttribute('aria-activedescendant');
                selectedIndex = -1;
            };

            const renderResults = (showAll = false) => {
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
                    empty.className = 'staff-search-empty';
                    empty.textContent = @json(__('لا توجد صفحة مطابقة'));
                    results.append(empty);
                } else {
                    matches.forEach((item, index) => {
                        const link = document.createElement('a');
                        link.href = item.href;
                        link.id = `staff-page-search-result-${index}`;
                        link.setAttribute('role', 'option');
                        link.setAttribute('aria-selected', 'false');
                        const label = document.createElement('span');
                        label.textContent = item.label;
                        const path = document.createElement('small');
                        path.textContent = new URL(item.href).pathname;
                        link.append(label, path);
                        link.addEventListener('click', () => sessionStorage.setItem(storageKey, item.label));
                        results.append(link);
                    });
                }

                results.hidden = false;
                input.setAttribute('aria-expanded', 'true');
            };

            const selectResult = index => {
                const options = [...results.querySelectorAll('[role="option"]')];
                if (!options.length) return;
                selectedIndex = (index + options.length) % options.length;
                options.forEach((option, optionIndex) => {
                    option.setAttribute('aria-selected', String(optionIndex === selectedIndex));
                });
                input.setAttribute('aria-activedescendant', options[selectedIndex].id);
            };

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
                if (!event.target.closest('.staff-page-search')) closeResults();
            });
        })();
    </script>
    <script src="{{ asset('js/infinite-scroll.js') }}"></script>
</body>
</html>
