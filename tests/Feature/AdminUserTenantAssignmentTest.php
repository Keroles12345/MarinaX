<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminUserTenantAssignmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('password_hash')->nullable();
            $table->string('role');
            $table->boolean('status')->default(true);
            $table->foreignId('tenant_id')->nullable();
            $table->boolean('is_super_admin')->default(false);
            $table->string('security_question')->nullable();
            $table->string('security_answer_hash')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'username']);
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
        Schema::dropIfExists('audit_log');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');

        parent::tearDown();
    }

    public function test_admin_created_user_is_assigned_to_the_admins_company(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Current company', 'status' => true]);
        $otherTenant = Tenant::query()->create(['name' => 'Other company', 'status' => true]);
        User::withoutGlobalScopes()->create([
            'name' => 'Other Tenant User',
            'username' => 'shared-username',
            'email' => 'other-staff@example.test',
            'password' => Hash::make('OtherPass123'),
            'role' => 'staff',
            'status' => true,
            'tenant_id' => $otherTenant->id,
        ]);
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

        $this->actingAs($admin, 'admin')
            ->postJson('/api/users', [
                'name' => 'New Staff',
                'username' => ' SHARED-USERNAME ',
                'email' => 'staff@example.test',
                'role' => 'staff',
                'password' => 'StaffPass123',
                'tenant_id' => $otherTenant->id,
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('users', [
            'username' => 'shared-username',
            'tenant_id' => $tenant->id,
        ]);
        $this->assertDatabaseHas('users', [
            'username' => 'shared-username',
            'tenant_id' => $otherTenant->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->postJson('/api/users', [
                'name' => 'Duplicate Staff',
                'username' => 'shared-username',
                'email' => 'duplicate@example.test',
                'role' => 'staff',
                'password' => 'StaffPass123',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('username');
    }
}
