<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->string('api_key_prefix', 24)->nullable();
            $table->string('api_key_hash', 64)->nullable();
            $table->date('api_key_expires_at')->nullable();
            $table->timestamp('api_key_last_used_at')->nullable();
            $table->json('api_key_scopes')->nullable();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('password_hash')->nullable();
            $table->rememberToken();
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

    public function test_dashboard_shows_tenant_permissions_instead_of_financial_performance(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Dashboard Test Company', 'status' => true]);
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
            ->assertSee('صلاحيات المستخدمين')
            ->assertSee('الوحدات والإجراءات المصرح بها')
            ->assertSee('Dashboard Test Company')
            ->assertDontSee('الأداء المالي لكل شركة')
            ->assertDontSee('صافي الربح')
            ->assertDontSee('الإيرادات')
            ->assertDontSee('المصروفات');
    }

    public function test_new_tenant_gets_a_booking_company_record(): void
    {
        $mainTenant = Tenant::query()->create(['name' => 'الشركة الرئيسية', 'status' => true]);
        DB::table('extras')->insert([
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

        $tenantId = (int) DB::table('tenants')
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

    public function test_super_admin_can_generate_and_revoke_a_company_api_key(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Company API', 'status' => true]);
        $superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Platform Admin',
            'username' => 'api-platform-admin',
            'email' => 'api-platform@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'admin',
            'status' => true,
            'is_super_admin' => true,
        ]);

        $scopes = ['trips:read', 'bookings:create'];
        $response = $this->actingAs($superAdmin, 'superadmin')
            ->post(route('superadmin.tenants.api-key.generate', $tenant), [
                'expires_at' => today()->addMonth()->toDateString(),
                'scopes' => $scopes,
            ])
            ->assertRedirect(route('superadmin.tenants.apis'))
            ->assertSessionHas('generated_api_key');

        $key = $response->getSession()->get('generated_api_key');
        $this->assertStringStartsWith('mx_live_', $key);
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'api_key_prefix' => substr($key, 0, 24),
            'api_key_hash' => hash('sha256', $key),
            'api_key_expires_at' => today()->addMonth()->startOfDay()->toDateTimeString(),
        ]);
        $this->assertSame($scopes, json_decode(DB::table('tenants')
            ->where('id', $tenant->id)
            ->value('api_key_scopes'), true));

        $page = $this->get(route('superadmin.tenants.apis'))
            ->assertOk()
            ->assertSee('واجهات API الشركات')
            ->assertSee('Company API')
            ->assertSee(substr($key, 0, 24))
            ->assertSee('trips:read')
            ->assertSee('bookings:read')
            ->assertSee('تفاصيل الحجز')
            ->assertSee('api-success-overlay', false)
            ->assertSee('مبروك! تم إنشاء API بنجاح')
            ->assertSee('تاريخ انتهاء الصلاحية')
            ->assertSee(today()->addMonth()->toDateString())
            ->assertSee('/api/v1/trips')
            ->assertSee('id="api-company-filter"', false)
            ->assertSee('فتح التوثيق التفاعلي')
            ->assertSee('مفاتيح فعالة')
            ->assertSee('class="api-company-key-value"', false)
            ->assertSee('data-copy-target="generated-api-key" data-copy-message="key"', false)
            ->assertSee('نسخ مفتاح API')
            ->assertSee('data-copy-message="endpoint"', false)
            ->assertSee('نسخ الرابط')
            ->assertSee('Authorization: Bearer YOUR_API_KEY')
            ->assertSee('navigator.clipboard.writeText', false)
            ->assertSee('Ctrl+C', false)
            ->assertSee($key);
        $this->assertSame(1, substr_count($page->getContent(), $key));
        $this->get(route('superadmin.tenants.apis'))
            ->assertOk()
            ->assertDontSee($key)
            ->assertSee(substr($key, 0, 24));

        $this->delete(route('superadmin.tenants.api-key.revoke', $tenant))
            ->assertRedirect(route('superadmin.tenants.apis'));
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'api_key_prefix' => null,
            'api_key_hash' => null,
            'api_key_expires_at' => null,
        ]);
    }

    public function test_generated_api_key_is_not_returned_by_management_page_after_one_time_display(): void
    {
        $tenant = Tenant::query()->create(['name' => 'One Time API', 'status' => true]);
        $superAdmin = User::withoutGlobalScopes()->create([
            'name' => 'Platform Admin',
            'username' => 'one-time-platform-admin',
            'email' => 'one-time-api@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'admin',
            'status' => true,
            'is_super_admin' => true,
        ]);
        $response = $this->actingAs($superAdmin, 'superadmin')->post(
            route('superadmin.tenants.api-key.generate', $tenant),
            ['expires_at' => today()->addMonth()->toDateString(), 'scopes' => ['trips:read']],
        );
        $key = $response->getSession()->get('generated_api_key');

        $this->get(route('superadmin.tenants.apis'))->assertOk()->assertSee($key);
        $this->get(route('superadmin.tenants.apis'))->assertOk()->assertDontSee($key);
        $this->assertFalse(Schema::hasColumn('tenants', 'api_key_encrypted'));
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

    public function test_admin_can_log_in_to_staff_dashboard_from_staff_login_page(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Company A', 'status' => true]);
        $admin = User::withoutGlobalScopes()->create([
            'name' => 'Company Admin',
            'username' => 'company-admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('CompanyPass123'),
            'role' => 'admin',
            'status' => true,
            'tenant_id' => $tenant->id,
            'is_super_admin' => false,
        ]);

        $this->post(route('staff.login.handle'), [
            'email' => $admin->email,
            'password' => 'CompanyPass123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin, 'staff');
        $this->assertGuest('admin');
    }

    public function test_staff_login_remembers_user_by_default(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Company A', 'status' => true]);
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

        $this->post(route('staff.login.handle'), [
            'email' => $staff->email,
            'password' => 'StaffPass123',
            'remember' => '1',
        ])->assertRedirect(route('dashboard'));

        self::assertNotEmpty($staff->fresh()->getRememberToken());
    }
}
