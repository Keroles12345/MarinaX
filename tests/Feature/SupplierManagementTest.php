<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('boats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('supplier_id')->nullable();
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
        foreach (['audit_log', 'settings', 'boats', 'suppliers', 'users', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_admin_can_manage_company_suppliers_and_archive_without_deleting_them(): void
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'name' => 'Supplier Test Company',
            'status' => true,
        ]);
        $admin = User::withoutGlobalScopes()->create([
            'name' => 'Supplier Admin',
            'username' => 'supplier-admin',
            'email' => 'supplier-admin@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'admin',
            'status' => true,
            'tenant_id' => $tenantId,
        ]);
        $this->grantAllSystemPermissions($admin);

        $this->actingAs($admin, 'admin')
            ->get(route('accounting.suppliers'))
            ->assertOk()
            ->assertSee('إدارة الموردين');

        $this->post(route('accounting.suppliers.store'), [
            'name' => 'Test Supplier',
            'phone' => '123456',
            'email' => 'supplier@example.test',
            'address' => 'Supplier address',
        ])->assertRedirect(route('accounting.suppliers'));

        $supplierId = (int) DB::table('suppliers')->where('name', 'Test Supplier')->value('id');
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplierId,
            'tenant_id' => $tenantId,
            'status' => true,
        ]);

        $this->put(route('accounting.suppliers.update', $supplierId), [
            'name' => 'Updated Supplier',
            'phone' => '654321',
            'email' => 'updated@example.test',
            'address' => 'Updated address',
            'status' => '1',
        ])->assertRedirect(route('accounting.suppliers'));

        $this->delete(route('accounting.suppliers.destroy', $supplierId))
            ->assertRedirect(route('accounting.suppliers'));
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplierId,
            'name' => 'Updated Supplier',
            'tenant_id' => $tenantId,
            'status' => false,
        ]);
    }

    public function test_add_supplier_validation_error_is_rendered_inside_the_add_modal(): void
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'name' => 'Supplier Validation Company',
            'status' => true,
        ]);
        $admin = User::withoutGlobalScopes()->create([
            'name' => 'Supplier Admin',
            'username' => 'supplier-validation-admin',
            'email' => 'supplier-validation-admin@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'admin',
            'status' => true,
            'tenant_id' => $tenantId,
        ]);
        $this->grantAllSystemPermissions($admin);
        $supplierPage = route('accounting.suppliers');

        $this->actingAs($admin, 'admin')
            ->followingRedirects()
            ->from($supplierPage)
            ->post(route('accounting.suppliers.store'), [
                '_form_type' => 'create',
                'name' => 'Invalid Supplier',
                'email' => 'not-an-email',
            ])
            ->assertOk()
            ->assertSeeInOrder([
                'id="create-supplier-modal"',
                'class="alert err"',
                'email field must be a valid email address',
            ], false);
    }
}
