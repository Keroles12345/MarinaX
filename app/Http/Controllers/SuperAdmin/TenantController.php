<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AccountChartService;
use App\Support\SystemPermissions;
use App\Support\TenantApiScopes;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantController extends Controller
{
    private const USER_ROLES = ['admin', 'accountant', 'viewer', 'account_user', 'staff', 'supervisor', 'customer'];

    public function loginForm(): View|RedirectResponse
    {
        if (Auth::guard('superadmin')->check()) {
            return redirect()->route('superadmin.dashboard');
        }

        return view('superadmin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        TenantContext::clear();

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $guard = Auth::guard('superadmin');
        if (! $guard->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->except('password'))
                ->withErrors(['email' => 'بيانات دخول سوبر فايزر غير صحيحة.']);
        }

        $user = $guard->user();
        if (! $user->is_super_admin || ! $user->status) {
            $guard->logout();
            $request->session()->regenerate();

            return back()
                ->withInput($request->except('password'))
                ->withErrors(['email' => 'هذا الحساب غير مصرح له بدخول لوحة سوبر فايزر.']);
        }

        $request->session()->regenerate();

        Auth::shouldUse('superadmin');

        return redirect()->intended(route('superadmin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('superadmin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('superadmin.login');
    }

    public function dashboard(): View
    {
        $tenants = Tenant::query()
            ->withCount(['users' => fn ($query) => $query->withoutGlobalScopes()])
            ->orderBy('name')
            ->get();
        $companyAdmins = User::withoutGlobalScopes()
            ->where('role', 'admin')
            ->where('is_super_admin', false)
            ->with(['permissions' => fn ($query) => $query->where('guard_name', 'web')])
            ->get(['id', 'tenant_id', 'system_permissions_managed'])
            ->groupBy('tenant_id');
        $tenants->each(function (Tenant $tenant) use ($companyAdmins): void {
            $admins = $companyAdmins->get($tenant->id, collect());
            $adminCount = $admins->count();
            $managedAdminCount = $admins->where('system_permissions_managed', true)->count();
            $hasFullPermissions = $adminCount > $managedAdminCount;
            $permissionNames = [];

            if ($hasFullPermissions) {
                $permissionNames = SystemPermissions::names();
            } else {
                foreach ($admins as $admin) {
                    foreach ($admin->permissions as $permission) {
                        $permissionNames[] = $permission->name;
                    }
                }
                $permissionNames = array_values(array_intersect(
                    array_unique($permissionNames),
                    SystemPermissions::names()
                ));
            }

            $actionsByModule = [];
            foreach ($permissionNames as $permissionName) {
                if (! str_starts_with($permissionName, 'system.')) {
                    continue;
                }

                $parts = explode('.', $permissionName);
                if (count($parts) !== 3) {
                    continue;
                }

                [, $module, $action] = $parts;
                if (! isset(SystemPermissions::MODULES[$module])
                    || ! in_array($action, SystemPermissions::MODULES[$module]['actions'], true)
                    || ! isset(SystemPermissions::ACTIONS[$action])) {
                    continue;
                }

                $actionsByModule[$module][$action] = true;
            }

            $permissionModules = [];
            foreach (SystemPermissions::MODULES as $module => $definition) {
                $actions = array_values(array_filter(
                    $definition['actions'],
                    fn (string $action): bool => isset($actionsByModule[$module][$action])
                        && isset(SystemPermissions::ACTIONS[$action])
                ));
                if ($actions === []) {
                    continue;
                }

                $permissionModules[] = [
                    'module' => $module,
                    'label' => $definition['label'],
                    'actions' => array_map(
                        fn (string $action): string => SystemPermissions::ACTIONS[$action],
                        $actions
                    ),
                ];
            }

            $tenant->setAttribute('admin_count', $adminCount);
            $tenant->setAttribute('managed_admin_count', $managedAdminCount);
            $tenant->setAttribute(
                'permission_mode',
                $hasFullPermissions ? 'full' : ($adminCount > 0 ? 'managed' : 'none')
            );
            $tenant->setAttribute(
                'permission_count',
                count($permissionNames)
            );
            $tenant->setAttribute('permission_modules', $permissionModules);
            $tenant->setAttribute('has_full_permissions', $hasFullPermissions);
        });
        $usersQuery = User::withoutGlobalScopes()
            ->where('is_super_admin', false);
        $totalUsers = (clone $usersQuery)->count();
        $activeUsers = (clone $usersQuery)->where('status', true)->count();
        $inactiveUsers = (clone $usersQuery)->where('status', false)->count();
        $unassignedUsers = (clone $usersQuery)->whereNull('tenant_id')->count();
        $recentUsers = (clone $usersQuery)
            ->with('tenant:id,name')
            ->latest()
            ->limit(6)
            ->get(['id', 'name', 'email', 'role', 'status', 'tenant_id', 'created_at']);

        return view('superadmin.tenants.index', [
            'tenants' => $tenants,
            'recentUsers' => $recentUsers,
            'page' => 'dashboard',
            'stats' => [
                'companies' => $tenants->count(),
                'active_companies' => $tenants->where('status', true)->count(),
                'suspended_companies' => $tenants->where('status', false)->count(),
                'users' => $totalUsers,
                'active_users' => $activeUsers,
                'inactive_users' => $inactiveUsers,
                'unassigned_users' => $unassignedUsers,
            ],
        ]);
    }

    public function index(): View
    {
        $tenants = Tenant::query()
            ->withCount(['users' => fn ($query) => $query->withoutGlobalScopes()])
            ->orderBy('name')
            ->get();

        return view('superadmin.tenants.index', ['page' => 'companies', 'tenants' => $tenants]);
    }

    public function apisIndex(Request $request): View
    {
        $tenants = Tenant::query()->orderBy('name')->get();
        $tenants->each(function (Tenant $tenant): void {
            $expired = $tenant->api_key_expires_at && today()->gt($tenant->api_key_expires_at);
            $tenant->setAttribute(
                'api_key_status',
                ! $tenant->api_key_prefix
                    ? 'لم يتم إنشاء مفتاح API لهذه الشركة'
                    : ($expired ? 'انتهت صلاحية مفتاح API' : 'مفتاح API مفعّل')
            );
            $tenant->setAttribute(
                'api_key_status_class',
                ! $tenant->api_key_prefix ? 'is-missing' : ($expired ? 'is-expired' : '')
            );
            $tenant->setAttribute(
                'api_key_expiry_label',
                $tenant->api_key_expires_at
                    ? 'تاريخ الانتهاء: '.$tenant->api_key_expires_at->format('Y-m-d')
                    : 'لا يوجد تاريخ انتهاء محدد'
            );
            $tenant->setAttribute(
                'api_key_last_used_label',
                $tenant->api_key_last_used_at
                    ? $tenant->api_key_last_used_at->format('Y-m-d H:i:s')
                    : 'لم يُستخدم بعد'
            );
            $tenant->setAttribute(
                'api_key_scopes_label',
                implode(', ', $tenant->api_key_scopes ?? TenantApiScopes::DEFAULT)
            );
        });

        return view('superadmin.tenants.index', [
            'page' => 'company-apis',
            'tenants' => $tenants,
            'availableApiScopes' => TenantApiScopes::AVAILABLE,
            'generatedApiKey' => $request->session()->pull('generated_api_key'),
            'generatedApiCompany' => $request->session()->pull('generated_api_company'),
            'apiEndpoints' => [
                ['method' => 'GET', 'path' => '/trips', 'label' => 'قائمة الرحلات', 'scope' => 'trips:read'],
                ['method' => 'GET', 'path' => '/trips/{id}', 'label' => 'تفاصيل الرحلة', 'scope' => 'trips:read'],
                ['method' => 'GET', 'path' => '/companies', 'label' => 'الشركات التابعة', 'scope' => 'companies:read'],
                ['method' => 'GET', 'path' => '/currencies', 'label' => 'العملات', 'scope' => 'currencies:read'],
                ['method' => 'POST', 'path' => '/bookings', 'label' => 'إنشاء حجز', 'scope' => 'bookings:create'],
                ['method' => 'GET', 'path' => '/bookings/{id}', 'label' => 'تفاصيل الحجز', 'scope' => 'bookings:read'],
                ['method' => 'PATCH', 'path' => '/bookings/{id}/status', 'label' => 'تحديث حالة الحجز', 'scope' => 'bookings:manage'],
                ['method' => 'POST', 'path' => '/bookings/{id}/payments', 'label' => 'إنشاء طلب دفع', 'scope' => 'payments:create'],
                ['method' => 'POST', 'path' => '/payments/{id}/refund', 'label' => 'طلب استرداد', 'scope' => 'payments:refund'],
            ],
        ]);
    }

    public function createTenantPage(): View
    {
        return view('superadmin.tenants.index', ['page' => 'create-company']);
    }

    public function createUserPage(): View
    {
        $tenants = Tenant::query()->where('status', true)->orderBy('name')->get();

        return view('superadmin.tenants.index', ['page' => 'create-user', 'tenants' => $tenants]);
    }

    public function usersIndex(Request $request): View
    {
        $filters = $request->validate([
            'tenant_id' => ['nullable', 'integer', Rule::exists('tenants', 'id')],
        ]);
        $users = User::withoutGlobalScopes()
            ->where('is_super_admin', false)
            ->when(
                filled($filters['tenant_id'] ?? null),
                fn ($query) => $query->where('tenant_id', $filters['tenant_id'])
            )
            ->with('tenant:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'email', 'role', 'status', 'tenant_id']);
        $tenants = Tenant::query()->orderBy('name')->get();

        return view('superadmin.tenants.index', [
            'page' => 'users',
            'users' => $users,
            'tenants' => $tenants,
            'selectedTenantId' => $filters['tenant_id'] ?? '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data): void {
            $tenant = Tenant::query()->create($data + ['status' => true]);
            DB::table('companies')->insert([
                'tenant_id' => $tenant->id,
                'name' => $tenant->name,
                'address' => $tenant->address,
                'phone' => $tenant->phone,
                'email' => $tenant->email,
                'status' => $tenant->status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $sourceTenantId = DB::table('tenants')->where('name', 'الشركة الرئيسية')->value('id');
            $defaultExtras = DB::table('extras')
                ->where('tenant_id', $sourceTenantId ?? $tenant->id)
                ->get(['name', 'price', 'rate_tax', 'status']);

            foreach ($defaultExtras as $extra) {
                DB::table('extras')->insert([
                    'tenant_id' => $tenant->id,
                    'name' => $extra->name,
                    'price' => $extra->price,
                    'rate_tax' => $extra->rate_tax,
                    'status' => $extra->status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            app(AccountChartService::class)->provisionTenant((int) $tenant->id);
        });

        return redirect()->route('superadmin.tenants.index')->with('status', 'تم إنشاء الشركة ودليل الحسابات الأساسي بنجاح.');
    }

    public function createUser(Request $request): RedirectResponse
    {
        $request->merge([
            'username' => Str::lower(trim((string) $request->input('username'))),
        ]);
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->where('tenant_id', $request->input('tenant_id'))],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:'.implode(',', self::USER_ROLES)],
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Za-z]/', 'regex:/\d/'],
        ]);

        $tenant = Tenant::query()->findOrFail($data['tenant_id']);
        abort_unless($tenant->status, 422, 'لا يمكن إضافة مستخدم إلى شركة موقوفة.');

        User::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => trim($data['name']),
            'username' => Str::lower(trim($data['username'])),
            'email' => Str::lower(trim($data['email'])),
            'password' => Hash::make($data['password']),
            'password_hash' => Hash::make($data['password']),
            'role' => $data['role'],
            'status' => true,
            'is_super_admin' => false,
        ]);

        return redirect()->route('superadmin.users.index')->with('status', 'تم إنشاء المستخدم وربطه بالشركة.');
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ]);

        $tenant->update($data);

        return redirect()->route('superadmin.tenants.index')->with('status', 'تم تحديث بيانات الشركة.');
    }

    public function generateApiKey(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'expires_at' => ['required', 'date', 'after_or_equal:today'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => ['required', 'string', Rule::in(TenantApiScopes::AVAILABLE)],
        ]);
        $key = 'mx_live_'.Str::random(64);
        $tenant->forceFill([
            'api_key_prefix' => substr($key, 0, 24),
            'api_key_hash' => hash('sha256', $key),
            'api_key_expires_at' => $data['expires_at'],
            'api_key_scopes' => array_values(array_unique($data['scopes'])),
        ])->save();

        return redirect()
            ->route('superadmin.tenants.apis')
            ->with('status', 'تم إنشاء مفتاح API. انسخه الآن؛ لن يظهر مرة أخرى.')
            ->with('generated_api_key', $key)
            ->with('generated_api_company', $tenant->name);
    }

    public function revokeApiKey(Tenant $tenant): RedirectResponse
    {
        $tenant->forceFill([
            'api_key_prefix' => null,
            'api_key_hash' => null,
            'api_key_expires_at' => null,
            'api_key_scopes' => null,
        ])->save();

        return redirect()->route('superadmin.tenants.apis')->with('status', 'تم إلغاء مفتاح API للشركة.');
    }

    public function assignUser(Request $request, int $userId): RedirectResponse
    {
        $user = User::withoutGlobalScopes()
            ->where('is_super_admin', false)
            ->findOrFail($userId);

        $data = $request->validate([
            'tenant_id' => ['required', 'integer', Rule::exists('tenants', 'id')->where('status', true)],
        ]);

        $user->tenant_id = $data['tenant_id'];
        $user->save();

        return redirect()->route('superadmin.users.index')->with('status', 'تم إسناد المستخدم للشركة.');
    }

    public function updateUser(Request $request, int $userId): RedirectResponse
    {
        $user = User::withoutGlobalScopes()
            ->where('is_super_admin', false)
            ->findOrFail($userId);

        $request->merge([
            'username' => Str::lower(trim((string) $request->input('username'))),
        ]);
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', Rule::exists('tenants', 'id')->where('status', true)],
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->where('tenant_id', $request->input('tenant_id'))->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'in:'.implode(',', self::USER_ROLES)],
            'status' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'regex:/[A-Za-z]/', 'regex:/\d/'],
        ]);

        $user->name = trim($data['name']);
        $user->username = Str::lower(trim($data['username']));
        $user->email = Str::lower(trim($data['email']));
        $user->role = $data['role'];
        $user->status = (bool) $data['status'];
        $user->tenant_id = $data['tenant_id'];
        if (filled($data['password'] ?? null)) {
            $user->password = Hash::make($data['password']);
            $user->password_hash = Hash::make($data['password']);
        }
        $user->save();

        return redirect()->route('superadmin.users.index')->with('status', 'تم تحديث بيانات المستخدم.');
    }
}
