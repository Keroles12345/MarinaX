@extends('superadmin.layout')

@section('title', __('إدارة صلاحيات النظام'))
@section('page-title', __('إدارة صلاحيات النظام'))

@section('body')
    <style>
        .system-permissions{display:grid;gap:18px}
        .system-permissions-card{padding:22px;border:1px solid #e7edf5;border-radius:16px;background:#fff;box-shadow:0 6px 20px #142c4b08}
        .system-permissions-card h2{margin:0 0 7px;color:#1b3558;font-size:18px}
        .system-permissions-note{margin:0;color:#66758b;font-size:13px;line-height:1.7}
        .system-permissions-selectors{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:14px;margin-top:18px}
        .system-permissions-selectors label{display:grid;gap:7px;color:#45546a;font-size:13px;font-weight:700}
        .system-permissions-selectors select{width:100%;min-height:42px;padding:9px 11px;border:1px solid #d5deea;border-radius:9px;background:#fff;color:#172033;font:inherit}
        .system-permissions-alert{padding:13px 16px;border:1px solid #b7ebc9;border-radius:10px;background:#ecfdf3;color:#067647}
        .system-permissions-error{padding:13px 16px;border:1px solid #fee4e2;border-radius:10px;background:#fff1f0;color:#b42318}
        .system-permissions-table{width:100%;border-collapse:collapse;margin-top:18px}
        .system-permissions-table th,.system-permissions-table td{padding:12px 10px;border-bottom:1px solid #edf1f6;text-align:center}
        .system-permissions-table th:first-child,.system-permissions-table td:first-child{text-align:right}
        [dir="ltr"] .system-permissions-table th:first-child,[dir="ltr"] .system-permissions-table td:first-child{text-align:left}
        .system-permissions-table th{color:#66758b;font-size:12px}
        .system-permissions-table td:first-child{color:#263a58;font-size:13px;font-weight:700}
        .system-permissions-table input{width:17px;height:17px;accent-color:#245ca6}
        .system-permissions-row-select{display:flex;align-items:center;justify-content:flex-start;gap:9px}
        .system-permissions-row-select label,.system-permissions-all-select{display:inline-flex;align-items:center;gap:7px;color:#45546a;font-size:12px;font-weight:700;white-space:nowrap}
        .system-permissions-submit{display:flex;justify-content:flex-start;gap:10px;margin-top:18px}
        .system-permissions-submit button{min-height:40px;padding:9px 17px;border:0;border-radius:9px;background:#245ca6;color:#fff;font:inherit;font-weight:750;cursor:pointer}
        .system-permissions-empty{padding:28px 12px;color:#66758b;text-align:center}
        @media(max-width:600px){.system-permissions-card{padding:15px;overflow-x:auto}.system-permissions-table{min-width:520px}}
    </style>

    <main class="system-permissions">
        <section class="system-permissions-card">
            <h2>{{ __('اختيار الشركة والمستخدم') }}</h2>
            <p class="system-permissions-note">{{ __('اختر الشركة ثم حساب المدير لإسناد صلاحيات النظام له. مدير الشركة يدير صلاحيات موظفيه من لوحة الإدارة.') }}</p>
            @if(session('status'))
                <p class="system-permissions-alert" role="status">{{ session('status') }}</p>
            @endif
            @if($errors->any())
                <div class="system-permissions-error" role="alert">
                    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            <form method="GET" action="{{ route('superadmin.permissions.index') }}" class="system-permissions-selectors">
                <label for="tenant_id">{{ __('الشركة') }}
                    <select id="tenant_id" name="tenant_id" onchange="const userSelect = this.form.querySelector('[name=user_id]'); if (userSelect) userSelect.value = ''; this.form.submit()">
                        <option value="">{{ __('اختر الشركة') }}</option>
                        @foreach($tenants as $tenant)
                            <option value="{{ $tenant->id }}" @selected($selectedTenant?->id === $tenant->id)>{{ $tenant->name }}</option>
                        @endforeach
                    </select>
                </label>
                @if($selectedTenant)
                    <label for="user_id">{{ __('المستخدم') }}
                        <select id="user_id" name="user_id" onchange="this.form.submit()">
                            <option value="">{{ __('اختر المستخدم') }}</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected($selectedUser?->id === $user->id)>
                                    {{ $user->name }} — {{ $user->email }} ({{ $user->role }})
                                </option>
                            @endforeach
                        </select>
                    </label>
                @endif
            </form>
        </section>

        @if($selectedTenant && ! $users->count())
            <section class="system-permissions-card system-permissions-empty">{{ __('لا يوجد مديرون مرتبطون بهذه الشركة.') }}</section>
        @elseif($selectedUser)
            <form method="POST" action="{{ route('superadmin.permissions.update') }}" class="system-permissions-card">
                @csrf
                <input type="hidden" name="tenant_id" value="{{ $selectedTenant->id }}">
                <input type="hidden" name="user_id" value="{{ $selectedUser->id }}">
                <h2>{{ $selectedUser->name }}</h2>
                <p class="system-permissions-note">{{ __('حدد صلاحيات مدير الشركة. سيتمكن من منح موظفيه فقط الصلاحيات التي اعتمدتها له من صلاحيات الموظفين أدناه، وأي صلاحية تسحبها ستتوقف عن العمل لهم.') }}</p>
                <table class="system-permissions-table">
                    <thead>
                        <tr>
                            <th>
                                <label class="system-permissions-all-select">
                                    <input type="checkbox" data-permission-all>
                                    {{ __('تحديد الكل') }}
                                </label>
                            </th>
                            @foreach($actions as $action => $label)
                                <th>{{ __($label) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($modules as $module => $definition)
                            <tr>
                                <td>
                                    <div class="system-permissions-row-select">
                                        <span>{{ __($definition['label']) }}</span>
                                        <label>
                                            <input type="checkbox" data-permission-row="{{ $module }}">
                                            {{ __('الكل') }}
                                        </label>
                                    </div>
                                </td>
                                @foreach($actions as $action => $label)
                                    <td>
                                        @if(in_array($action, $definition['actions'], true))
                                            @php($permissionName = "system.{$module}.{$action}")
                                            <input type="checkbox" name="permissions[]" value="{{ $permissionName }}" data-permission="{{ $module }}" aria-label="{{ __($label) }} — {{ __($definition['label']) }}" @checked(in_array($permissionName, $assignedPermissions, true))>
                                        @else
                                            <span aria-hidden="true">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="system-permissions-submit">
                    <button type="submit">{{ __('حفظ الصلاحيات') }}</button>
                </div>
            </form>
            <script>
                (() => {
                    const form = document.currentScript.previousElementSibling;
                    if (!form) return;

                    const permissions = [...form.querySelectorAll('[data-permission]')];
                    const selectAll = form.querySelector('[data-permission-all]');
                    const rowSelectors = [...form.querySelectorAll('[data-permission-row]')];

                    const syncSelector = (selector, checkboxes) => {
                        const checkedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
                        selector.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
                        selector.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
                    };

                    const syncSelectors = () => {
                        syncSelector(selectAll, permissions);
                        rowSelectors.forEach((selector) => {
                            const rowPermissions = permissions.filter(
                                (permission) => permission.dataset.permission === selector.dataset.permissionRow
                            );
                            syncSelector(selector, rowPermissions);
                        });
                    };

                    selectAll.addEventListener('change', () => {
                        permissions.forEach((permission) => {
                            permission.checked = selectAll.checked;
                        });
                        syncSelectors();
                    });

                    rowSelectors.forEach((selector) => {
                        selector.addEventListener('change', () => {
                            permissions
                                .filter((permission) => permission.dataset.permission === selector.dataset.permissionRow)
                                .forEach((permission) => {
                                    permission.checked = selector.checked;
                                });
                            syncSelectors();
                        });
                    });

                    permissions.forEach((permission) => permission.addEventListener('change', syncSelectors));
                    syncSelectors();
                })();
            </script>
        @elseif($selectedTenant)
            <section class="system-permissions-card system-permissions-empty">{{ __('اختر مديرًا لعرض صلاحيات النظام وتعديلها.') }}</section>
        @endif
    </main>
@endsection
