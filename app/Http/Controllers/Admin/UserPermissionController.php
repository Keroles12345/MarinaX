<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SystemPermissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class UserPermissionController extends Controller
{
    public function index()
    {
        $delegableNames = SystemPermissions::staffDelegableNames(auth()->user());
        $users = User::query()->where('role', 'staff')->orderBy('name')->get();

        $selectedUser = null;

        if (request()->filled('user')) {
            $selectedUser = User::query()
                ->where('role', 'staff')
                ->findOrFail(request('user'));
        }
        $assignedPermissionNames = $selectedUser
            ? $selectedUser->permissions()->where('guard_name', 'web')->pluck('permissions.name')->all()
            : [];

        return view('admin.pages.user_permissions', [
            'users' => $users,
            'modules' => collect(SystemPermissions::STAFF_MANAGED_MODULES)
                ->mapWithKeys(function (string $module) use ($delegableNames): array {
                    $definition = SystemPermissions::MODULES[$module];
                    $definition['actions'] = array_values(array_filter(
                        $definition['actions'],
                        fn (string $action): bool => in_array(
                            SystemPermissions::name($module, $action),
                            $delegableNames,
                            true
                        )
                    ));

                    return $definition['actions'] ? [$module => $definition] : [];
                })
                ->all(),
            'actions' => SystemPermissions::ACTIONS,
            'delegableNames' => $delegableNames,
            'selectedUser' => $selectedUser,
            'assignedPermissionNames' => $assignedPermissionNames,
            'user' => auth()->user(),
            'roleName' => 'مدير النظام',
        ]);
    }

    public function update(Request $request)
    {
        $delegableNames = SystemPermissions::staffDelegableNames($request->user());
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'tenant_exists:users,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($delegableNames)],
        ]);

        $user = User::query()
            ->where('role', 'staff')
            ->findOrFail($validated['user_id']);
        $managedNames = SystemPermissions::staffNames();
        $preservedPermissions = $user->permissions()
            ->whereNotIn('name', $managedNames)
            ->get();
        $selectedPermissions = collect($validated['permissions'] ?? [])
            ->unique()
            ->map(fn (string $name) => Permission::findOrCreate($name, 'web'));

        $user->syncPermissions($preservedPermissions->merge($selectedPermissions));
        $user->forceFill(['system_permissions_managed' => true])->save();

        return redirect()
            ->route('admin.user-permissions', [
                'user' => $user->id,
            ])
            ->with('success', 'تم حفظ صلاحيات الموظف بنجاح.');
    }
}
