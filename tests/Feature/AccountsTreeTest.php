<?php

namespace Tests\Feature;

use App\Http\Controllers\AccountingController;
use App\Models\User;
use App\Services\AccountChartService;
use App\Support\TenantContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountsTreeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('acc_no');
            $table->string('name');
            $table->string('type')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('budget', 15, 2)->default(0);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });
        Schema::create('account_templates', function (Blueprint $table) {
            $table->id();
            $table->string('acc_no')->unique();
            $table->string('name');
            $table->string('type')->default('');
            $table->timestamps();
        });
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('status');
        });
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('entry_id');
            $table->foreignId('account_id');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
        });
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable();
            $table->string('user')->nullable();
            $table->string('action')->nullable();
            $table->text('details')->nullable();
            $table->timestamp('at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        foreach (['audit_log', 'journal_lines', 'journal_entries', 'account_templates', 'accounts', 'users', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_tree_includes_other_and_untyped_accounts(): void
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'name' => 'Company',
            'status' => true,
        ]);
        $admin = User::withoutGlobalScopes()->create([
            'name' => 'Company Admin',
            'username' => 'company-admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'admin',
            'status' => true,
            'tenant_id' => $tenantId,
        ]);
        DB::table('accounts')->insert([
            ['tenant_id' => $tenantId, 'acc_no' => '6101', 'name' => 'Other type account', 'type' => 'أخرى', 'opening_balance' => 0, 'budget' => 0, 'is_system' => false],
            ['tenant_id' => $tenantId, 'acc_no' => '6201', 'name' => 'Untyped account', 'type' => '', 'opening_balance' => 0, 'budget' => 0, 'is_system' => false],
            ['tenant_id' => $tenantId + 1, 'acc_no' => '1101', 'name' => 'Other company account', 'type' => 'أصول', 'opening_balance' => 0, 'budget' => 0, 'is_system' => false],
        ]);

        $this->actingAs($admin, 'admin')
            ->getJson('/api/accounts/tree')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Other type account'])
            ->assertJsonFragment(['name' => 'Untyped account'])
            ->assertJsonMissing(['name' => 'Other company account']);
    }

    public function test_new_company_gets_shared_account_definitions_with_independent_details(): void
    {
        $tenantId = DB::table('tenants')->insertGetId(['name' => 'New Company', 'status' => true]);
        DB::table('account_templates')->insert([
            'acc_no' => '1101',
            'name' => 'Cash',
            'type' => 'أصول',
        ]);

        $service = new AccountChartService();
        $service->provisionTenant($tenantId);

        $account = DB::table('accounts')->where('tenant_id', $tenantId)->where('acc_no', '1101')->first();
        self::assertSame('Cash', $account->name);
        self::assertSame(1, (int) $account->is_system);
        self::assertSame(0.0, (float) $account->opening_balance);
        self::assertSame(0.0, (float) $account->budget);

        DB::table('accounts')->where('id', $account->id)->update(['opening_balance' => 250, 'budget' => 800]);
        $service->provisionTenant($tenantId);

        $account = DB::table('accounts')->where('id', $account->id)->first();
        self::assertSame(250.0, (float) $account->opening_balance);
        self::assertSame(800.0, (float) $account->budget);
    }

    public function test_shared_account_identity_is_locked_but_company_details_can_change(): void
    {
        $tenantId = DB::table('tenants')->insertGetId(['name' => 'Company', 'status' => true]);
        $admin = User::withoutGlobalScopes()->create([
            'name' => 'Company Admin',
            'username' => 'shared-account-admin',
            'email' => 'shared-account-admin@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'admin',
            'status' => true,
            'tenant_id' => $tenantId,
        ]);
        $accountId = DB::table('accounts')->insertGetId([
            'tenant_id' => $tenantId,
            'acc_no' => '1101',
            'name' => 'Cash',
            'type' => 'أصول',
            'opening_balance' => 0,
            'budget' => 0,
            'is_system' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->putJson('/api/accounts/'.$accountId, [
                'acc_no' => '1101',
                'name' => 'Cash',
                'type' => 'أصول',
                'opening_balance' => 250,
                'budget' => 800,
            ])
            ->assertOk();

        $this->assertDatabaseHas('accounts', [
            'id' => $accountId,
            'tenant_id' => $tenantId,
            'name' => 'Cash',
            'opening_balance' => 250,
            'budget' => 800,
            'is_system' => true,
        ]);

        $this->putJson('/api/accounts/'.$accountId, [
            'acc_no' => '1101',
            'name' => 'Renamed shared cash',
            'type' => 'أصول',
            'opening_balance' => 250,
            'budget' => 800,
        ])->assertUnprocessable();

        $this->deleteJson('/api/accounts/'.$accountId)->assertStatus(400);
    }

    public function test_tree_rejects_requests_without_a_company_context(): void
    {
        TenantContext::clear();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        (new AccountingController())->accountsTree();
    }
}
