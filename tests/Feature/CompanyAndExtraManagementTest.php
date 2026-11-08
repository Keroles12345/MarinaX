<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CompanyAndExtraManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSystemPermissionTables();

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('status')->default(true);
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role');
            $table->boolean('status')->default(true);
            $table->foreignId('tenant_id')->nullable();
            $table->boolean('is_super_admin')->default(false);
            $table->timestamps();
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
        Schema::create('extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('rate_tax', 5, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('symbol')->nullable();
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('prices_company', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('id_company');
            $table->foreignId('id_trip');
            $table->foreignId('id_currency');
            $table->decimal('price_adult', 10, 2)->default(0);
            $table->decimal('price_child', 10, 2)->default(0);
            $table->decimal('price_infant', 10, 2)->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('key');
            $table->text('value')->nullable();
        });
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('user')->nullable();
            $table->string('action')->nullable();
            $table->text('details')->nullable();
            $table->timestamp('at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        foreach (['audit_log', 'settings', 'prices_company', 'currencies', 'trips', 'extras', 'companies', 'users', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_admin_can_manage_company_and_extra_records_within_its_tenant(): void
    {
        $tenantId = DB::table('tenants')->insertGetId(['name' => 'Current Company', 'status' => true]);
        $otherTenantId = DB::table('tenants')->insertGetId(['name' => 'Other Company', 'status' => true]);
        $otherCompanyId = DB::table('companies')->insertGetId([
            'tenant_id' => $otherTenantId,
            'name' => 'Private Company',
            'status' => true,
        ]);
        $otherExtraId = DB::table('extras')->insertGetId([
            'tenant_id' => $otherTenantId,
            'name' => 'Private Extra',
            'price' => 5,
            'rate_tax' => 0,
            'status' => true,
        ]);
        $admin = User::withoutGlobalScopes()->create([
            'name' => 'Resource Admin',
            'username' => 'resource-admin',
            'email' => 'resource-admin@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'admin',
            'status' => true,
            'tenant_id' => $tenantId,
        ]);
        $this->grantAllSystemPermissions($admin);

        $this->actingAs($admin, 'admin')
            ->get(route('accounting.companies'))
            ->assertOk()
            ->assertSee('إدارة الشركات')
            ->assertDontSee('Private Company');

        $this->post(route('accounting.companies.store'), [
            'name' => 'Local Company',
            'email' => 'local@example.test',
            'phone' => '12345',
            'address' => 'Cairo',
        ])->assertRedirect(route('accounting.companies'));

        $companyId = (int) DB::table('companies')->where('name', 'Local Company')->value('id');
        $this->put(route('accounting.companies.update', $companyId), [
            'name' => 'Updated Local Company',
            'status' => true,
        ])->assertRedirect(route('accounting.companies'));
        $this->delete(route('accounting.companies.destroy', $companyId))
            ->assertRedirect(route('accounting.companies'));
        $this->assertDatabaseHas('companies', [
            'id' => $companyId,
            'tenant_id' => $tenantId,
            'name' => 'Updated Local Company',
            'status' => false,
        ]);

        $this->get(route('accounting.extras'))
            ->assertOk()
            ->assertSee('الخدمات الإضافية')
            ->assertDontSee('Private Extra');

        $this->post(route('accounting.extras.store'), [
            'name' => 'Local Extra',
            'price' => 12.5,
            'rate_tax' => 14,
        ])->assertRedirect(route('accounting.extras'));

        $extraId = (int) DB::table('extras')->where('name', 'Local Extra')->value('id');
        $this->put(route('accounting.extras.update', $extraId), [
            'name' => 'Updated Local Extra',
            'price' => 20,
            'rate_tax' => 14,
            'status' => true,
        ])->assertRedirect(route('accounting.extras'));
        $this->delete(route('accounting.extras.destroy', $extraId))
            ->assertRedirect(route('accounting.extras'));
        $this->assertDatabaseHas('extras', [
            'id' => $extraId,
            'tenant_id' => $tenantId,
            'name' => 'Updated Local Extra',
            'status' => false,
        ]);

        $this->assertDatabaseHas('companies', ['id' => $otherCompanyId, 'tenant_id' => $otherTenantId]);
        $this->assertDatabaseHas('extras', ['id' => $otherExtraId, 'tenant_id' => $otherTenantId]);
    }

    public function test_company_trip_prices_are_saved_and_displayed_in_egyptian_pounds(): void
    {
        $tenantId = DB::table('tenants')->insertGetId(['name' => 'Price Company', 'status' => true]);
        $otherTenantId = DB::table('tenants')->insertGetId(['name' => 'Other Price Company', 'status' => true]);
        $companyId = DB::table('companies')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Local Tour Operator',
            'status' => true,
        ]);
        $otherCompanyId = DB::table('companies')->insertGetId([
            'tenant_id' => $otherTenantId,
            'name' => 'Other Tour Operator',
            'status' => true,
        ]);
        $tripId = DB::table('trips')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Hurghada Trip',
            'status' => true,
        ]);
        DB::table('currencies')->insert([
            ['id' => 1, 'name' => 'Egyptian Pound', 'code' => 'EGP', 'is_default' => true, 'is_active' => true],
            ['id' => 2, 'name' => 'US Dollar', 'code' => 'USD', 'is_default' => false, 'is_active' => true],
        ]);
        $admin = User::withoutGlobalScopes()->create([
            'name' => 'Price Admin',
            'username' => 'price-admin',
            'email' => 'price-admin@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'admin',
            'status' => true,
            'tenant_id' => $tenantId,
        ]);
        $this->grantAllSystemPermissions($admin);

        $this->actingAs($admin, 'admin')
            ->get(route('accounting.company-prices'))
            ->assertOk()
            ->assertSee('العملة الأساسية')
            ->assertSee('EGP');

        $this->post(route('accounting.company-prices.save'), [
            'company_id' => $companyId,
            'trip_id' => $tripId,
            'price_adult' => '1250.50',
            'price_child' => '850',
            'price_infant' => '100',
        ])->assertRedirect(route('accounting.company-prices'));

        $this->assertDatabaseHas('prices_company', [
            'tenant_id' => $tenantId,
            'id_company' => $companyId,
            'id_trip' => $tripId,
            'id_currency' => 1,
            'price_adult' => '1250.50',
            'price_child' => '850.00',
            'price_infant' => '100.00',
        ]);

        $this->from(route('accounting.company-prices'))
            ->post(route('accounting.company-prices.save'), [
                'company_id' => $otherCompanyId,
                'trip_id' => $tripId,
                'price_adult' => 1,
                'price_child' => 1,
                'price_infant' => 0,
            ])
            ->assertRedirect(route('accounting.company-prices'))
            ->assertSessionHasErrors('company_id');

        $this->actingAs($admin, 'admin')
            ->get(route('accounting.company-prices'))
            ->assertSee('Local Tour Operator')
            ->assertSee('Hurghada Trip')
            ->assertDontSee('Other Tour Operator');
    }
}
