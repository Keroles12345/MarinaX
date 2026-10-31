<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AccountChartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Support\TenantContext;

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
        $profitTotals = DB::table('tenants as t')
            ->leftJoin('accounts as a', 'a.tenant_id', '=', 't.id')
            ->leftJoin('journal_lines as jl', function ($join): void {
                $join->on('jl.account_id', '=', 'a.id')
                    ->on('jl.tenant_id', '=', 't.id');
            })
            ->leftJoin('journal_entries as je', function ($join): void {
                $join->on('je.id', '=', 'jl.entry_id')
                    ->on('je.tenant_id', '=', 't.id')
                    ->where('je.status', 'posted');
            })
            ->select('t.id')
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN je.id IS NOT NULL AND a.type = 'إيرادات' THEN jl.credit - jl.debit ELSE 0 END), 0) as revenue_total"
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN je.id IS NOT NULL AND a.type = 'مصروفات' THEN jl.debit - jl.credit ELSE 0 END), 0) as expense_total"
            )
            ->groupBy('t.id')
            ->get()
            ->keyBy('id');
        $tenants->each(function (Tenant $tenant) use ($profitTotals): void {
            $totals = $profitTotals->get($tenant->id);
            $tenant->revenue_total = round((float) ($totals?->revenue_total ?? 0), 2);
            $tenant->expense_total = round((float) ($totals?->expense_total ?? 0), 2);
            $tenant->net_profit = round($tenant->revenue_total - $tenant->expense_total, 2);
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
