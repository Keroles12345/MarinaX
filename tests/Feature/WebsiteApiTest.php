<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsiteApiTest extends TestCase
{
    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = 'mx_live_'.str_repeat('b', 64);

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->string('api_key_prefix', 24)->nullable();
            $table->string('api_key_hash', 64)->nullable();
            $table->date('api_key_expires_at')->nullable();
            $table->timestamp('api_key_last_used_at')->nullable();
        });
        Schema::create('regions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
        });
        Schema::create('trips', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->unsignedBigInteger('region_id')->nullable();
            $table->time('time_trip')->nullable();
            $table->text('description')->nullable();
            $table->json('includes')->nullable();
            $table->json('excludes')->nullable();
            $table->json('faqs')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('trip_images', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('trip_id');
            $table->string('path');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('hotels', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->boolean('status')->default(true);
        });
        Schema::create('trips_hotels', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('trip_id');
            $table->unsignedBigInteger('hotel_id');
            $table->time('pickup_time')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('status')->default(true);
        });
        Schema::create('currencies', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('symbol')->nullable();
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
        });
        Schema::create('prices_company', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('id_company');
            $table->unsignedBigInteger('id_trip');
            $table->unsignedBigInteger('id_currency');
            $table->decimal('price_adult', 10, 2)->default(0);
            $table->decimal('price_child', 10, 2)->default(0);
            $table->decimal('price_infant', 10, 2)->default(0);
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('username');
            $table->string('email');
            $table->string('password');
            $table->string('role');
            $table->boolean('status')->default(true);
            $table->boolean('is_super_admin')->default(false);
            $table->timestamps();
        });
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('operations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('booking_id');
            $table->unsignedBigInteger('id_trip');
            $table->unsignedBigInteger('id_company');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('trip_hotel_id')->nullable();
            $table->unsignedBigInteger('id_currency');
            $table->date('trip_date');
            $table->time('trip_time')->nullable();
            $table->time('time_pickup')->nullable();
            $table->integer('adults')->default(0);
            $table->integer('children')->default(0);
            $table->integer('infants')->default(0);
            $table->decimal('price_adult', 10, 2)->default(0);
            $table->decimal('price_child', 10, 2)->default(0);
            $table->decimal('price_infant', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('amount_total', 10, 2)->default(0);
            $table->decimal('totalamount', 10, 2)->default(0);
            $table->decimal('amount_remaining', 10, 2)->default(0);
            $table->string('status_payment')->default('pending');
            $table->string('status')->default('pending');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('by_created');
            $table->timestamps();
        });

        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        foreach ([
            'operations', 'customers', 'users', 'prices_company', 'currencies', 'companies',
            'trips_hotels', 'hotels', 'trip_images', 'trips', 'regions', 'tenants',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_api_key_scopes_trip_catalog_and_accepts_a_priced_booking(): void
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'name' => 'Sea Tours',
            'status' => true,
            'api_key_prefix' => substr($this->apiKey, 0, 24),
            'api_key_hash' => hash('sha256', $this->apiKey),
        ]);
        $otherTenantId = DB::table('tenants')->insertGetId([
            'name' => 'Other Tours',
            'status' => true,
        ]);
        $regionId = DB::table('regions')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Hurghada',
            'is_active' => true,
        ]);
        $tripId = DB::table('trips')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Island trip',
            'region_id' => $regionId,
            'time_trip' => '08:00:00',
            'description' => 'A day on the island',
            'includes' => json_encode(['Lunch']),
            'excludes' => json_encode(['Hotel']),
            'faqs' => json_encode([['question' => 'When?', 'answer' => 'In the morning.']]),
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('trips')->insert([
            'tenant_id' => $otherTenantId,
            'name' => 'Other tenant trip',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $imagePath = "trips/{$tenantId}/island.webp";
        Storage::disk('public')->put($imagePath, 'webp');
        DB::table('trip_images')->insert([
            'tenant_id' => $tenantId,
            'trip_id' => $tripId,
            'path' => $imagePath,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $companyId = DB::table('companies')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Local Operator',
            'status' => true,
        ]);
        $currencyId = DB::table('currencies')->insertGetId([
            'name' => 'Egyptian Pound',
            'code' => 'EGP',
            'symbol' => 'E£',
            'exchange_rate' => 1,
            'is_default' => true,
            'is_active' => true,
        ]);
        DB::table('prices_company')->insert([
            'tenant_id' => $tenantId,
            'id_company' => $companyId,
            'id_trip' => $tripId,
            'id_currency' => $currencyId,
            'price_adult' => 100,
            'price_child' => 50,
            'price_infant' => 0,
        ]);
        $userId = DB::table('users')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Company Admin',
            'username' => 'company-admin',
            'email' => 'admin@example.test',
            'password' => 'hashed',
            'role' => 'admin',
            'status' => true,
            'is_super_admin' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/trips')
            ->assertUnauthorized();

        $this->withToken($this->apiKey)
            ->getJson('/api/v1/trips')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Island trip')
            ->assertJsonPath('data.0.includes.0', 'Lunch')
            ->assertJsonPath('data.0.faqs.0.answer', 'In the morning.')
            ->assertJsonPath('data.0.primary_image', Storage::disk('public')->url($imagePath))
            ->assertJsonPath('data.0.images.0', Storage::disk('public')->url($imagePath))
            ->assertJsonPath('data.0.prices.0.company_id', $companyId);
        $this->assertNotNull(DB::table('tenants')->where('id', $tenantId)->value('api_key_last_used_at'));

        $this->withToken($this->apiKey)
            ->postJson('/api/v1/bookings', [
                'trip_id' => $tripId,
                'company_id' => $companyId,
                'currency_id' => $currencyId,
                'trip_date' => today()->toDateString(),
                'customer' => ['name' => 'Website Customer', 'phone' => '01000000000'],
                'adults' => 2,
                'children' => 1,
                'infants' => 0,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.amount', '250.00')
            ->assertJsonPath('data.currency', 'EGP');

        $this->assertDatabaseHas('operations', [
            'tenant_id' => $tenantId,
            'id_trip' => $tripId,
            'id_company' => $companyId,
            'by_created' => $userId,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('customers', [
            'tenant_id' => $tenantId,
            'name' => 'Website Customer',
        ]);
    }

    public function test_expired_api_key_is_rejected(): void
    {
        DB::table('tenants')->insert([
            'name' => 'Expired API Tenant',
            'status' => true,
            'api_key_prefix' => substr($this->apiKey, 0, 24),
            'api_key_hash' => hash('sha256', $this->apiKey),
            'api_key_expires_at' => today()->subDay()->toDateString(),
        ]);

        $this->withToken($this->apiKey)
            ->getJson('/api/v1/trips')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'انتهت صلاحية مفتاح API.');
    }

    public function test_api_documentation_routes_are_public_and_serve_the_openapi_contract(): void
    {
        $this->get('/api/documentation')
            ->assertOk()
            ->assertHeader('content-type', 'text/html; charset=UTF-8');
        $this->assertFileExists(public_path('api-documentation.html'));
        $this->assertStringContainsString('SwaggerUIBundle', file_get_contents(public_path('api-documentation.html')));

        $this->get('/api/openapi.yaml')
            ->assertOk()
            ->assertHeader('content-type', 'application/yaml; charset=UTF-8');
        $this->assertFileExists(public_path('openapi.yaml'));
        $openApi = file_get_contents(public_path('openapi.yaml'));
        foreach (app('router')->getRoutes() as $route) {
            if (! str_starts_with((string) $route->getName(), 'api.')) {
                continue;
            }

            $path = preg_replace('/^api\//', '', $route->uri());
            $this->assertStringContainsString("  /{$path}:", $openApi);
        }
    }

    public function test_tenant_api_rate_limit_is_enforced(): void
    {
        DB::table('tenants')->insert([
            'name' => 'Rate Limited Tenant',
            'status' => true,
            'api_key_prefix' => substr($this->apiKey, 0, 24),
            'api_key_hash' => hash('sha256', $this->apiKey),
        ]);
        Log::spy();

        for ($request = 0; $request < 60; $request++) {
            $this->withToken($this->apiKey)->getJson('/api/v1/trips')->assertOk();
        }

        $this->withToken($this->apiKey)
            ->getJson('/api/v1/trips')
            ->assertTooManyRequests();
    }
}
