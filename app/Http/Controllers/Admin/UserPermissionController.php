<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class UserPermissionController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        $selectedUser = null;

        if (request()->filled('user')) {
            $selectedUser = User::findOrFail(request('user'));
        }
        $assignedPermissionIds = $selectedUser
            ? $selectedUser->permissions()->where('guard_name', 'web')->pluck('permissions.id')->all()
            : [];

        return view('admin.pages.user_permissions', [
            'users' => $users,
            'permissions' => $permissions,
            'selectedUser' => $selectedUser,
            'assignedPermissionIds' => $assignedPermissionIds,
            'user' => auth()->user(),
            'roleName' => 'مدير النظام',
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'tenant_exists:users,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', 'web'),
            ],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $validated['permissions'] ?? [])
            ->get();
        $user->syncPermissions($permissions);

        return redirect()
            ->route('admin.user-permissions', [
                'user' => $user->id,
            ])
            ->with('success', 'Permissions updated successfully.');
    }
}
