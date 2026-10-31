<header class="staff-topbar">
    <button id="staff-sidebar-toggle" class="staff-menu-toggle" type="button" aria-label="{{ __('إظهار القائمة الجانبية') }}" aria-expanded="false" title="{{ __('إظهار القائمة الجانبية') }}">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
    </button>

    <h1 class="font-bold">{{ __(trim($__env->yieldContent('page-title', 'لوحة العمليات'))) }}</h1>
    @include('shared.language-switcher')

    <div class="staff-page-search">
        <label class="staff-sr-only" for="staff-page-search-input">{{ __('ابحث عن صفحة') }}</label>
        <div class="staff-page-search-control">
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.8"/>
                <path d="m16 16 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
            <input id="staff-page-search-input" type="search" autocomplete="off"
                role="combobox" aria-autocomplete="list" aria-expanded="false"
                aria-controls="staff-page-search-results"
                placeholder="{{ __('ابحث عن صفحة...') }}">
        </div>
        <div id="staff-page-search-results" class="staff-page-search-results" role="listbox" hidden></div>
    </div>

    <div class="staff-user-chip">
        <div class="staff-user-avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>
        <div>
            <span class="staff-user-name">{{ auth()->user()->name }}</span>
            <span class="staff-user-role">{{ auth()->user()->email ?? 'موظف' }}</span>
        </div>
    </div>
</header>
