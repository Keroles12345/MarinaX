<aside class="staff-sidebar">
    <div class="staff-brand">{{ __('بوابة العمليات') }}</div>

    <nav class="staff-nav" aria-label="{{ __('الرئيسية') }}">
        @systemPermission('staff_dashboard', 'view')
            <details class="staff-nav-category">
                <summary class="staff-nav-title">{{ __('الرئيسية') }}</summary>
                <div class="staff-nav-links">
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <span class="staff-nav-icon">🏠</span>{{ __('لوحة الموظف') }}
                    </a>
                </div>
            </details>
        @endsystemPermission

        @if(
            \App\Support\SystemPermissions::allows(auth('staff')->user(), 'operations', 'view')
            || \App\Support\SystemPermissions::allows(auth('staff')->user(), 'daily_operations', 'view')
            || \App\Support\SystemPermissions::allows(auth('staff')->user(), 'operation_assignments', 'view')
        )
            <details class="staff-nav-category" {{ request()->routeIs('operatoins.dashboard', 'daily-ops.*', 'operation-assignments.*') ? 'open' : '' }}>
                <summary class="staff-nav-title">{{ __('العمليات') }}</summary>
                <div class="staff-nav-links">
                @systemPermission('operations', 'view')
                    <a href="{{ route('operatoins.dashboard') }}" class="{{ request()->routeIs('operatoins.dashboard') ? 'active' : '' }}">
                        <span class="staff-nav-icon">🧭</span>{{ __('اختيار الرحلة') }}
                    </a>
                @endsystemPermission
                @systemPermission('daily_operations', 'view')
                <a href="{{ route('daily-ops.index') }}" class="{{ request()->routeIs('daily-ops.*') ? 'active' : '' }}">
                    <span class="staff-nav-icon">📋</span>{{ __('العمليات اليومية') }}
                </a>
                @endsystemPermission
                @systemPermission('operation_assignments', 'view')
                <a href="{{ route('operation-assignments.index') }}" class="{{ request()->routeIs('operation-assignments.*') ? 'active' : '' }}">
                    <span class="staff-nav-icon">👥</span>{{ __('توزيع العمليات') }}
                </a>
                @endsystemPermission
                </div>
            </details>
        @endif

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
