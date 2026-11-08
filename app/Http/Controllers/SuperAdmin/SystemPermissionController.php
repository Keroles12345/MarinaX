<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Support\SystemPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class SystemPermissionController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'tenant_id' => ['nullable', 'integer', Rule::exists('tenants', 'id')],
            'user_id' => ['nullable', 'integer'],
        ]);
        $tenants = Tenant::query()->orderBy('name')->get();
        $selectedTenant = isset($data['tenant_id'])
            ? $tenants->firstWhere('id', (int) $data['tenant_id'])
            : null;
        $users = $selectedTenant
            ? User::withoutGlobalScopes()
                ->where('tenant_id', $selectedTenant->id)
                ->where('role', 'admin')
                ->where('is_super_admin', false)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role'])
            : collect();
        $selectedUser = isset($data['user_id'])
            ? $users->firstWhere('id', (int) $data['user_id'])
            : null;

        abort_if(isset($data['user_id']) && ! $selectedUser, 404);

        $assignedPermissions = $selectedUser
            ? $selectedUser->permissions()->where('guard_name', 'web')->pluck('name')->all()
            : [];

        return view('superadmin.permissions.index', [
            'tenants' => $tenants,
            'users' => $users,
            'selectedTenant' => $selectedTenant,
            'selectedUser' => $selectedUser,
            'modules' => SystemPermissions::MODULES,
            'actions' => SystemPermissions::ACTIONS,
            'assignedPermissions' => $assignedPermissions,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', Rule::exists('tenants', 'id')],
            'user_id' => ['required', 'integer'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(SystemPermissions::names())],
        ]);

        $user = User::withoutGlobalScopes()
            ->where('tenant_id', $data['tenant_id'])
            ->where('role', 'admin')
            ->where('is_super_admin', false)
            ->findOrFail($data['user_id']);

        $managedNames = SystemPermissions::names();
        $preservedPermissions = $user->permissions()
            ->whereNotIn('name', $managedNames)
            ->get();
        $selectedPermissions = collect($data['permissions'] ?? [])
            ->unique()
            ->map(fn (string $name) => Permission::findOrCreate($name, 'web'));

        $user->syncPermissions($preservedPermissions->merge($selectedPermissions));
        $user->forceFill(['system_permissions_managed' => true])->save();

        return redirect()
            ->route('superadmin.permissions.index', [
                'tenant_id' => $data['tenant_id'],
                'user_id' => $user->id,
            ])
            ->with('status', 'تم حفظ صلاحيات المستخدم بنجاح.');
    }
}
