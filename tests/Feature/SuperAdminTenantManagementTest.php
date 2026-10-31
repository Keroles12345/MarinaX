<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SuperAdminTenantManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('password_hash')->nullable();
            $table->string('role');
            $table->boolean('status')->default(true);
            $table->foreignId('tenant_id')->nullable();
            $table->boolean('is_super_admin')->default(false);
            $table->string('security_question')->nullable();
            $table->timestamps();
        });
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable();
            $table->string('user')->nullable();
            $table->string('action')->nullable();
            $table->text('details')->nullable();
            $table->timestamp('at')->nullable();
        });
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('account_templates', function (Blueprint $table) {
            $table->id();
            $table->string('acc_no');
            $table->string('name');
            $table->string('type')->default('');
        });
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('acc_no');
            $table->string('name');
            $table->string('type')->default('');
            $table->boolean('is_system')->default(false);
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('budget', 15, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('movement_no');
            $table->date('entry_date');
            $table->string('region')->default('');
            $table->string('status')->default('draft');
            $table->timestamps();
        });
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('entry_id');
            $table->foreignId('account_id');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
        });
        Schema::create('extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('rate_tax', 5, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('audit_log');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('account_templates');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('extras');
        Schema::dropIfExists('tenants');

        parent::tearDown();
    }

    public function test_super_admin_can_create_a_company_user(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Company B', 'status' => true]);
        $superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Platform Admin',
            'username' => 'platform-admin',
            'email' => 'platform@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'admin',
            'status' => true,
            'is_super_admin' => true,
        ]);

        $this->actingAs($superAdmin, 'superadmin')
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('Company B');

        $this->post(route('superadmin.users.store'), [
                'tenant_id' => $tenant->id,
                'name' => 'Company Staff',
                'username' => 'company-staff',
                'email' => 'staff@example.test',
                'role' => 'staff',
                'password' => 'StaffPass123',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'username' => 'company-staff',
            'tenant_id' => $tenant->id,
            'status' => true,
            'is_super_admin' => false,
        ]);
    }

    public function test_super_admin_can_filter_users_by_company_on_the_users_page(): void
    {
        $firstTenant = Tenant::query()->create(['name' => 'Company A', 'status' => true]);
        $secondTenant = Tenant::query()->create(['name' => 'Company B', 'status' => true]);
        $superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Platform Admin',
            'username' => 'platform-admin',
            'email' => 'platform@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'admin',
            'status' => true,
            'is_super_admin' => true,
        ]);
        $firstUser = User::withoutGlobalScopes()->create([
            'name' => 'First Company User',
            'username' => 'first-company-user',
            'email' => 'first@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'staff',
            'status' => true,
            'tenant_id' => $firstTenant->id,
            'is_super_admin' => false,
        ]);
        User::withoutGlobalScopes()->create([
            'name' => 'Second Company User',
            'username' => 'second-company-user',
            'email' => 'second@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'staff',
            'status' => true,
            'tenant_id' => $secondTenant->id,
            'is_super_admin' => false,
        ]);

        $this->actingAs($superAdmin, 'superadmin')
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertViewHas('stats.users', 2);

        $this->get(route('superadmin.users.index', ['tenant_id' => $firstTenant->id]))
            ->assertOk()
            ->assertSee('first-company-user')
            ->assertDontSee('second-company-user')
            ->assertViewHas('users', fn ($users) => $users->count() === 1
                && $users->first()->id === $firstUser->id
                && $users->first()->status === true);
    }

    public function test_dashboard_shows_each_company_profit_from_posted_entries_only(): void
    {
        $firstTenant = Tenant::query()->create(['name' => 'Profitable Company', 'status' => true]);
        $secondTenant = Tenant::query()->create(['name' => 'Loss-making Company', 'status' => true]);
        $superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Platform Admin',
            'username' => 'platform-admin',
            'email' => 'platform@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'admin',
            'status' => true,
            'is_super_admin' => true,
        ]);
        $accounts = [];
        foreach ([
            [$firstTenant->id, 'إيرادات', '4101'],
            [$firstTenant->id, 'مصروفات', '5101'],
            [$secondTenant->id, 'إيرادات', '4101'],
            [$secondTenant->id, 'مصروفات', '5101'],
        ] as [$tenantId, $type, $number]) {
            $accounts[] = \Illuminate\Support\Facades\DB::table('accounts')->insertGetId([
                'tenant_id' => $tenantId,
                'acc_no' => $number,
                'name' => $type,
                'type' => $type,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $entries = [];
        foreach ([
            [$firstTenant->id, 'posted'],
            [$firstTenant->id, 'posted'],
            [$firstTenant->id, 'draft'],
            [$secondTenant->id, 'posted'],
            [$secondTenant->id, 'posted'],
        ] as $index => [$tenantId, $status]) {
            $entries[] = \Illuminate\Support\Facades\DB::table('journal_entries')->insertGetId([
                'tenant_id' => $tenantId,
                'movement_no' => 'PROFIT-'.$index,
                'entry_date' => today()->toDateString(),
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        \Illuminate\Support\Facades\DB::table('journal_lines')->insert([
            ['tenant_id' => $firstTenant->id, 'entry_id' => $entries[0], 'account_id' => $accounts[0], 'debit' => 0, 'credit' => 100],
            ['tenant_id' => $firstTenant->id, 'entry_id' => $entries[1], 'account_id' => $accounts[1], 'debit' => 20, 'credit' => 0],
            ['tenant_id' => $firstTenant->id, 'entry_id' => $entries[2], 'account_id' => $accounts[0], 'debit' => 0, 'credit' => 900],
            ['tenant_id' => $secondTenant->id, 'entry_id' => $entries[3], 'account_id' => $accounts[2], 'debit' => 0, 'credit' => 50],
            ['tenant_id' => $secondTenant->id, 'entry_id' => $entries[4], 'account_id' => $accounts[3], 'debit' => 75, 'credit' => 0],
        ]);

        $this->actingAs($superAdmin, 'superadmin')
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('صافي الربح')
            ->assertSee('الأداء المالي لكل شركة')
            ->assertDontSee('ملخص الشركات المالي')
            ->assertSee('أحدث الموظفين والمستخدمين')
            ->assertSeeInOrder(['الأداء المالي لكل شركة', 'أحدث الموظفين والمستخدمين'])
            ->assertViewHas('tenants', fn ($tenants) => $tenants->firstWhere('id', $firstTenant->id)->net_profit === 80.0
                && $tenants->firstWhere('id', $secondTenant->id)->net_profit === -25.0)
            ->assertSee('80.00')
            ->assertSee('-25.00');

        $this->withSession(['locale' => 'en'])
            ->actingAs($superAdmin, 'superadmin')
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Financial Performance by Company');
    }

    public function test_new_tenant_gets_a_booking_company_record(): void
    {
        $mainTenant = Tenant::query()->create(['name' => 'الشركة الرئيسية', 'status' => true]);
        \Illuminate\Support\Facades\DB::table('extras')->insert([
            'tenant_id' => $mainTenant->id,
            'name' => 'Lunch',
            'price' => 15,
            'rate_tax' => 14,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Platform Admin',
            'username' => 'platform-admin',
            'email' => 'platform@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'admin',
            'status' => true,
            'is_super_admin' => true,
        ]);

        $this->actingAs($superAdmin, 'superadmin')
            ->post(route('superadmin.tenants.store'), [
                'name' => 'New Booking Company',
                'email' => 'bookings@example.test',
                'phone' => '0100000000',
                'address' => 'Cairo',
            ])
            ->assertRedirect();

        $tenantId = (int) \Illuminate\Support\Facades\DB::table('tenants')
            ->where('name', 'New Booking Company')
            ->value('id');

        $this->assertDatabaseHas('companies', [
            'tenant_id' => $tenantId,
            'name' => 'New Booking Company',
            'email' => 'bookings@example.test',
            'status' => true,
        ]);
        $this->assertDatabaseHas('extras', [
            'tenant_id' => $tenantId,
            'name' => 'Lunch',
            'price' => 15,
            'status' => true,
        ]);
    }

    public function test_company_admin_cannot_access_super_admin_company_management(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Company A', 'status' => true]);
        $admin = User::withoutGlobalScopes()->create([
            'name' => 'Company Admin',
            'username' => 'company-admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'admin',
            'status' => true,
            'tenant_id' => $tenant->id,
            'is_super_admin' => false,
        ]);

        $this->actingAs($admin, 'superadmin')
            ->get(route('superadmin.dashboard'))
            ->assertForbidden();
    }

    public function test_super_admin_company_list_route_shows_the_company_management_page(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Company A', 'status' => true]);
        $superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Platform Admin',
            'username' => 'platform-admin',
            'email' => 'platform@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'admin',
            'status' => true,
            'is_super_admin' => true,
        ]);

        $this->actingAs($superAdmin, 'superadmin')
            ->get(route('superadmin.tenants.index'))
            ->assertOk()
            ->assertSee('إدارة الشركات')
            ->assertSee('Company A')
            ->assertSee('حفظ التعديلات')
            ->assertDontSee('إنشاء المستخدم النشط');

        $this->get(route('superadmin.tenants.create'))
            ->assertOk()
            ->assertSee('إنشاء الشركة')
            ->assertDontSee('حفظ التعديلات');

        $this->get(route('superadmin.users.create'))
            ->assertOk()
            ->assertSee('إنشاء المستخدم النشط')
            ->assertSee('Company A');

        $this->get(route('superadmin.users.index'))
            ->assertOk()
            ->assertSee('إدارة المستخدمين')
            ->assertDontSee('حفظ التعديلات');

        $this->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('ملخص المنصة')
            ->assertDontSee('إنشاء المستخدم النشط')
            ->assertDontSee('حفظ التعديلات');
    }

    public function test_super_admin_has_a_separate_login_and_rejects_company_admins(): void
    {
        $superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Platform Admin',
            'username' => 'platform-admin',
            'email' => 'platform@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'admin',
            'status' => true,
            'is_super_admin' => true,
        ]);

        $this->get(route('superadmin.login'))
            ->assertOk()
            ->assertSee('دخول مدير النظام');

        $this->post(route('superadmin.login.handle'), [
            'email' => $superAdmin->email,
            'password' => 'StrongPass123',
        ])->assertRedirect(route('superadmin.dashboard'));

        auth('superadmin')->logout();

        $companyAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Company Admin',
            'username' => 'company-admin',
            'email' => 'company@example.test',
            'password' => Hash::make('CompanyPass123'),
            'role' => 'admin',
            'status' => true,
            'tenant_id' => Tenant::query()->create(['name' => 'Company A', 'status' => true])->id,
            'is_super_admin' => false,
        ]);

        $this->actingAs($companyAdmin, 'admin')
            ->post(route('superadmin.login.handle'), [
                'email' => $superAdmin->email,
                'password' => 'StrongPass123',
            ])
            ->assertRedirect(route('superadmin.dashboard'));

        auth('superadmin')->logout();

        $this->from(route('superadmin.login'))
            ->post(route('superadmin.login.handle'), [
                'email' => $companyAdmin->email,
                'password' => 'CompanyPass123',
            ])
            ->assertRedirect(route('superadmin.login'))
            ->assertSessionHasErrors('email');
    }

    public function test_super_admin_can_edit_company_user_settings_and_password(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Company A', 'status' => true]);
        $superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Platform Admin',
            'username' => 'platform-admin',
            'email' => 'platform@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'admin',
            'status' => true,
            'is_super_admin' => true,
        ]);
        $user = User::withoutGlobalScopes()->create([
            'name' => 'Old Name',
            'username' => 'old-user',
            'email' => 'old@example.test',
            'password' => Hash::make('OldPass123'),
            'role' => 'staff',
            'status' => true,
            'tenant_id' => $tenant->id,
            'is_super_admin' => false,
        ]);

        $this->actingAs($superAdmin, 'superadmin')
            ->put(route('superadmin.users.update', $user->id), [
                'tenant_id' => $tenant->id,
                'name' => 'Updated Name',
                'username' => 'updated-user',
                'email' => 'updated@example.test',
                'role' => 'admin',
                'status' => '0',
                'password' => 'NewPass123',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'tenant_id' => $tenant->id,
            'name' => 'Updated Name',
            'role' => 'admin',
            'status' => false,
        ]);
        $this->assertTrue(Hash::check('NewPass123', $user->fresh()->password));
    }

    public function test_admin_and_super_admin_remain_logged_in_to_separate_dashboards(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Company A', 'status' => true]);
        $superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Platform Admin',
            'username' => 'platform-admin',
            'email' => 'platform@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'admin',
            'status' => true,
            'is_super_admin' => true,
        ]);
        $companyAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Company Admin',
            'username' => 'company-admin',
            'email' => 'company@example.test',
            'password' => Hash::make('CompanyPass123'),
            'role' => 'admin',
            'status' => true,
            'tenant_id' => $tenant->id,
            'is_super_admin' => false,
        ]);

        $this->post(route('superadmin.login.handle'), [
            'email' => $superAdmin->email,
            'password' => 'StrongPass123',
        ])->assertRedirect(route('superadmin.dashboard'));

        $this->post(route('admin.login.handle'), [
            'email' => $companyAdmin->email,
            'password' => 'CompanyPass123',
        ])->assertRedirect(route('accounting.home'));

        $this->assertAuthenticatedAs($superAdmin, 'superadmin');
        $this->assertAuthenticatedAs($companyAdmin, 'admin');
    }

    public function test_staff_admin_and_super_admin_remain_logged_in_together(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Company A', 'status' => true]);
        $companyAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Company Admin',
            'username' => 'company-admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('CompanyPass123'),
            'role' => 'admin',
            'status' => true,
            'tenant_id' => $tenant->id,
            'is_super_admin' => false,
        ]);
        $staff = User::withoutGlobalScopes()->create([
            'name' => 'Company Staff',
            'username' => 'company-staff',
            'email' => 'staff@example.test',
            'password' => Hash::make('StaffPass123'),
            'role' => 'staff',
            'status' => true,
            'tenant_id' => $tenant->id,
            'is_super_admin' => false,
        ]);

        $superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Platform Admin',
            'username' => 'platform-admin',
            'email' => 'platform@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'admin',
            'status' => true,
            'is_super_admin' => true,
        ]);

        $this->post(route('admin.login.handle'), [
            'email' => $companyAdmin->email,
            'password' => 'CompanyPass123',
        ])->assertRedirect(route('accounting.home'));
        $this->post(route('staff.login.handle'), [
            'email' => $staff->email,
            'password' => 'StaffPass123',
        ])->assertRedirect(route('dashboard'));
        $this->post(route('superadmin.login.handle'), [
            'email' => $superAdmin->email,
            'password' => 'StrongPass123',
        ])->assertRedirect(route('superadmin.dashboard'));

        $this->assertAuthenticatedAs($companyAdmin, 'admin');
        $this->assertAuthenticatedAs($staff, 'staff');
        $this->assertAuthenticatedAs($superAdmin, 'superadmin');
    }
}
