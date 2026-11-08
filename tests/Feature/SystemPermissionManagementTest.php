<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Support\SystemPermissions;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SystemPermissionManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role');
            $table->boolean('status')->default(true);
            $table->foreignId('tenant_id')->nullable();
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('system_permissions_managed')->default(false);
            $table->timestamps();
        });
        $this->setUpSystemPermissionTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');

        parent::tearDown();
    }

    public function test_super_admin_can_assign_exact_system_permissions_to_a_user_in_a_company(): void
    {
        $tenant = Tenant::query()->create(['name' => 'شركة الاختبار', 'status' => true]);
        $user = User::withoutGlobalScopes()->create([
            'name' => 'مدير الشركة',
            'username' => 'company-admin',
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => 'admin',
            'status' => true,
            'tenant_id' => $tenant->id,
        ]);
        $staff = User::withoutGlobalScopes()->create([
            'name' => 'موظف الشركة',
            'username' => 'company-staff',
            'email' => 'staff@example.test',
            'password' => 'password',
            'role' => 'staff',
            'status' => true,
            'tenant_id' => $tenant->id,
        ]);
        $superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.test',
            'password' => 'password',
            'role' => 'admin',
            'status' => true,
            'is_super_admin' => true,
        ]);

        $this->actingAs($superAdmin, 'superadmin')
            ->post(route('superadmin.permissions.update'), [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'permissions' => [
                    SystemPermissions::name('admin_dashboard', 'view'),
                    SystemPermissions::name('accounts', 'view'),
                ],
            ])
            ->assertRedirect(route('superadmin.permissions.index', [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
            ]));

        $user->refresh();
        $this->assertTrue($user->system_permissions_managed);
        $this->assertTrue(SystemPermissions::allows($user, 'admin_dashboard', 'view'));
        $this->assertFalse(SystemPermissions::allows($user, 'journal', 'view'));
        $this->assertEqualsCanonicalizing([
            SystemPermissions::name('admin_dashboard', 'view'),
            SystemPermissions::name('accounts', 'view'),
        ], $user->permissions()->pluck('name')->all());

        $this->get(route('superadmin.permissions.index', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]))
            ->assertOk()
            ->assertSee('إدارة صلاحيات النظام')
            ->assertSee('admin@example.test')
            ->assertDontSee('staff@example.test')
            ->assertSee('دليل الحسابات')
            ->assertSee('data-permission-all', false)
            ->assertSee('data-permission-row="accounts"', false);

        $this->actingAs($superAdmin, 'superadmin')
            ->post(route('superadmin.permissions.update'), [
                'tenant_id' => $tenant->id,
                'user_id' => $staff->id,
                'permissions' => [SystemPermissions::name('staff_dashboard', 'view')],
            ])
            ->assertNotFound();

        $this->assertFalse($staff->fresh()->system_permissions_managed);
    }

    public function test_user_cannot_save_permissions_for_another_companys_account(): void
    {
        $tenant = Tenant::query()->create(['name' => 'الشركة الأولى', 'status' => true]);
        $otherTenant = Tenant::query()->create(['name' => 'الشركة الثانية', 'status' => true]);
        $user = User::withoutGlobalScopes()->create([
            'name' => 'مستخدم شركة أخرى',
            'email' => 'other@example.test',
            'password' => 'password',
            'role' => 'admin',
            'status' => true,
            'tenant_id' => $otherTenant->id,
        ]);
        $superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.test',
            'password' => 'password',
            'role' => 'admin',
            'status' => true,
            'is_super_admin' => true,
        ]);

        $this->actingAs($superAdmin, 'superadmin')
            ->post(route('superadmin.permissions.update'), [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'permissions' => [SystemPermissions::name('admin_dashboard', 'view')],
            ])
            ->assertNotFound();

        $this->assertFalse($user->fresh()->system_permissions_managed);
        $this->assertDatabaseCount('model_has_permissions', 0);
    }

    public function test_managed_user_must_have_permission_for_admin_dashboard(): void
    {
        $tenant = Tenant::query()->create(['name' => 'شركة الاختبار', 'status' => true]);
        $user = User::withoutGlobalScopes()->create([
            'name' => 'مدير الشركة',
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => 'admin',
            'status' => true,
            'tenant_id' => $tenant->id,
            'system_permissions_managed' => true,
        ]);

        $this->actingAs($user, 'admin')->get('/admin/dashboard')->assertForbidden();

        $user->givePermissionTo(Permission::findOrCreate(
            SystemPermissions::name('admin_dashboard', 'view'),
            'web'
        ));

        $this->actingAs($user, 'admin')
            ->get('/admin/dashboard')
            ->assertRedirect(route('accounting.home'));
    }

    public function test_admin_requires_explicit_permissions_even_when_permissions_are_not_marked_managed(): void
    {
        $tenant = Tenant::query()->create(['name' => 'شركة الاختبار', 'status' => true]);
        $admin = User::withoutGlobalScopes()->create([
            'name' => 'مدير الشركة',
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => 'admin',
            'status' => true,
            'tenant_id' => $tenant->id,
            'system_permissions_managed' => false,
        ]);

        $this->assertFalse($admin->system_permissions_managed);
        $this->assertFalse(SystemPermissions::allows($admin, 'admin_dashboard', 'view'));
        $this->actingAs($admin, 'admin')->get('/admin/dashboard')->assertForbidden();

        $admin->givePermissionTo(Permission::findOrCreate(
            SystemPermissions::name('admin_dashboard', 'view'),
            'web'
        ));

        $this->assertTrue(SystemPermissions::allows($admin, 'admin_dashboard', 'view'));
        $this->actingAs($admin, 'admin')
            ->get('/admin/dashboard')
            ->assertRedirect(route('accounting.home'));
    }

    public function test_company_admin_can_assign_only_staff_permissions_to_staff_in_their_company(): void
    {
        $tenant = Tenant::query()->create(['name' => 'شركة الاختبار', 'status' => true]);
        $admin = User::withoutGlobalScopes()->create([
            'name' => 'مدير الشركة',
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => 'admin',
            'status' => true,
            'tenant_id' => $tenant->id,
            'system_permissions_managed' => true,
        ]);
        $admin->givePermissionTo([
            Permission::findOrCreate(SystemPermissions::name('users', 'view'), 'web'),
            Permission::findOrCreate(SystemPermissions::name('users', 'update'), 'web'),
            Permission::findOrCreate(SystemPermissions::name('staff_dashboard', 'view'), 'web'),
            Permission::findOrCreate(SystemPermissions::name('operations', 'view'), 'web'),
        ]);
        $staff = User::withoutGlobalScopes()->create([
            'name' => 'موظف الشركة',
            'email' => 'staff@example.test',
            'password' => 'password',
            'role' => 'staff',
            'status' => true,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.user-permissions'))
            ->assertOk()
            ->assertSee('staff@example.test')
            ->assertViewHas('users', fn ($users): bool => $users->count() === 1
                && $users->first()->is($staff))
            ->assertDontSee('system.accounts.view');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.user-permissions.update'), [
                'user_id' => $staff->id,
                'permissions' => [
                    SystemPermissions::name('staff_dashboard', 'view'),
                    SystemPermissions::name('operations', 'view'),
                ],
            ])
            ->assertRedirect(route('admin.user-permissions', ['user' => $staff->id]));

        $staff->refresh();
        $this->assertTrue($staff->system_permissions_managed);
        $this->assertTrue(SystemPermissions::allows($staff, 'staff_dashboard', 'view'));
        $this->assertTrue(SystemPermissions::allows($staff, 'operations', 'view'));
        $this->assertFalse(SystemPermissions::allows($staff, 'accounts', 'view'));

        $admin->revokePermissionTo(SystemPermissions::name('operations', 'view'));
        $this->assertFalse(SystemPermissions::allows($staff->fresh(), 'operations', 'view'));
    }

    public function test_company_admin_cannot_assign_permissions_not_granted_by_super_admin(): void
    {
        $tenant = Tenant::query()->create(['name' => 'شركة الاختبار', 'status' => true]);
        $admin = User::withoutGlobalScopes()->create([
            'name' => 'مدير الشركة',
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => 'admin',
            'status' => true,
            'tenant_id' => $tenant->id,
            'system_permissions_managed' => true,
        ]);
        $admin->givePermissionTo([
            Permission::findOrCreate(SystemPermissions::name('users', 'view'), 'web'),
            Permission::findOrCreate(SystemPermissions::name('users', 'update'), 'web'),
        ]);
        $staff = User::withoutGlobalScopes()->create([
            'name' => 'موظف الشركة',
            'email' => 'staff@example.test',
            'password' => 'password',
            'role' => 'staff',
            'status' => true,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.user-permissions.update'), [
                'user_id' => $staff->id,
                'permissions' => [SystemPermissions::name('staff_dashboard', 'view')],
            ])
            ->assertSessionHasErrors('permissions.0');

        $this->assertFalse($staff->fresh()->system_permissions_managed);
        $this->assertDatabaseCount('model_has_permissions', 2);
    }

}
