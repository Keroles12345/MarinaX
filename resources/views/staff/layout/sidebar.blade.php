<aside class="staff-sidebar">
    <div class="staff-brand">{{ __('بوابة العمليات') }}</div>

    <nav class="staff-nav" aria-label="{{ __('الرئيسية') }}">
        <details class="staff-nav-category">
            <summary class="staff-nav-title">{{ __('الرئيسية') }}</summary>
            <div class="staff-nav-links">
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="staff-nav-icon">🏠</span>{{ __('لوحة الموظف') }}
                </a>
            </div>
        </details>

        <details class="staff-nav-category" {{ request()->routeIs('operatoins.dashboard', 'daily-ops.*', 'operation-assignments.*') ? 'open' : '' }}>
            <summary class="staff-nav-title">{{ __('العمليات') }}</summary>
            <div class="staff-nav-links">
                @can('operations.edit')
                    <a href="{{ route('operatoins.dashboard') }}" class="{{ request()->routeIs('operatoins.dashboard') ? 'active' : '' }}">
                        <span class="staff-nav-icon">🧭</span>{{ __('اختيار الرحلة') }}
                    </a>
                @endcan
                <a href="{{ route('daily-ops.index') }}" class="{{ request()->routeIs('daily-ops.*') ? 'active' : '' }}">
                    <span class="staff-nav-icon">📋</span>{{ __('العمليات اليومية') }}
                </a>
                <a href="{{ route('operation-assignments.index') }}" class="{{ request()->routeIs('operation-assignments.*') ? 'active' : '' }}">
                    <span class="staff-nav-icon">👥</span>{{ __('توزيع العمليات') }}
                </a>
            </div>
        </details>

        @if(Route::has('staff.logout'))
            <form action="{{ route('staff.logout') }}" method="POST">
                @csrf
                <button type="submit" class="staff-logout">
                    <span class="staff-nav-icon">🚪</span>{{ __('تسجيل الخروج') }}
                </button>
            </form>
        @endif
    </nav>

    <div class="staff-sidebar-footer">© {{ __('جميع الحقوق محفوظة') }}</div>
</aside>
