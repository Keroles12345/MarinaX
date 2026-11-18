<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
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
            $table->json('api_key_scopes')->nullable();
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
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('amount_remaining', 10, 2)->default(0);
            $table->string('status_payment')->default('pending');
            $table->string('status')->default('pending');
            $table->string('booking_status')->default('pending');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('by_created');
            $table->timestamps();
        });
        Schema::create('tenant_api_idempotency_keys', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->char('key_hash', 64);
            $table->char('request_hash', 64);
            $table->json('response_body')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->unsignedBigInteger('operation_id')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'key_hash']);
        });
        Schema::create('operation_status_history', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('operation_id');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('actor_type')->default('system');
            $table->string('actor_identifier')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('operation_id');
            $table->string('provider');
            $table->decimal('amount', 10, 2);
            $table->decimal('amount_refunded', 10, 2)->default(0);
            $table->string('currency_code', 10);
            $table->string('status')->default('pending');
            $table->string('transaction_reference')->nullable();
            $table->char('idempotency_key_hash', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('succeeded_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });
        Schema::create('payment_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('provider');
            $table->string('event_id');
            $table->char('payload_hash', 64);
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'event_id']);
        });
        Schema::create('payment_refunds', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('payment_id');
            $table->char('idempotency_key_hash', 64);
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending');
            $table->string('transaction_reference')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'idempotency_key_hash']);
        });
        Schema::create('booking_notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('operation_id');
            $table->string('event_type');
            $table->string('recipient');
            $table->timestamps();
            $table->unique(['tenant_id', 'operation_id', 'event_type', 'recipient']);
        });

        Storage::fake('public');
        Notification::fake();
    }

    protected function tearDown(): void
    {
        foreach ([
            'booking_notification_deliveries', 'payment_refunds', 'payment_webhook_events', 'payments',
            'operation_status_history', 'tenant_api_idempotency_keys', 'operations', 'customers', 'users', 'prices_company', 'currencies', 'companies',
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
        $otherTripId = DB::table('trips')->insertGetId([
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
        $this->withToken($this->apiKey)->getJson('/api/v1/trips/'.$otherTripId)->assertNotFound();

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
            foreach ($route->methods() as $method) {
                if (in_array($method, ['HEAD', 'OPTIONS'], true)) {
                    continue;
                }
                $this->assertStringContainsString(strtolower($method).':', $openApi);
            }
        }
    }

    public function test_idempotency_replays_original_booking_and_rejects_reused_key_with_different_payload(): void
    {
        $fixture = $this->seedBookingFixture();
        $payload = $this->bookingPayload($fixture);
        $headers = ['Idempotency-Key' => 'website-order-0001'];

        $first = $this->withToken($this->apiKey)->postJson('/api/v1/bookings', $payload, $headers)
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');
        $original = $first->json();

        $this->withToken($this->apiKey)->postJson('/api/v1/bookings', $payload, $headers)
            ->assertCreated()
            ->assertHeader('Idempotent-Replay', 'true')
            ->assertExactJson($original);

        $this->assertSame(1, DB::table('operations')->count());
        $this->assertSame(1, DB::table('customers')->count());
        $this->assertSame(1, DB::table('tenant_api_idempotency_keys')->count());
        $this->assertSame(1, DB::table('booking_notification_deliveries')->where('event_type', 'created')->count());

        $payload['adults'] = 3;
        $this->withToken($this->apiKey)->postJson('/api/v1/bookings', $payload, $headers)
            ->assertStatus(409);
        $this->assertSame(1, DB::table('operations')->count());
    }

    public function test_api_scopes_limit_catalog_and_booking_access(): void
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'name' => 'Trips Only',
            'status' => true,
            'api_key_prefix' => substr($this->apiKey, 0, 24),
            'api_key_hash' => hash('sha256', $this->apiKey),
            'api_key_scopes' => json_encode(['trips:read']),
        ]);
        DB::table('regions')->insert(['tenant_id' => $tenantId, 'name' => 'North', 'is_active' => true]);
        $this->withToken($this->apiKey)->getJson('/api/v1/companies')
            ->assertForbidden()
            ->assertJsonPath('required_scope', 'companies:read');
        $this->withToken($this->apiKey)->getJson('/api/v1/trips')
            ->assertOk();
    }

    public function test_booking_lifecycle_records_valid_transitions_and_rejects_invalid_transitions(): void
    {
        $fixture = $this->seedBookingFixture(['bookings:create', 'bookings:read', 'bookings:manage']);
        $response = $this->withToken($this->apiKey)->postJson('/api/v1/bookings', $this->bookingPayload($fixture))
            ->assertCreated();
        $bookingId = $response->json('data.booking_id');

        $this->withToken($this->apiKey)->patchJson('/api/v1/bookings/'.$bookingId.'/status', [
            'status' => 'confirmed',
            'reason' => 'Reviewed by operator.',
        ])->assertOk()->assertJsonPath('data.status', 'confirmed');

        $this->withToken($this->apiKey)->getJson('/api/v1/bookings/'.$bookingId)
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->withToken($this->apiKey)->patchJson('/api/v1/bookings/'.$bookingId.'/status', [
            'status' => 'confirmed',
        ])->assertUnprocessable();
        $this->withToken($this->apiKey)->patchJson('/api/v1/bookings/'.$bookingId.'/status', [
            'status' => 'paid',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('operation_status_history', [
            'tenant_id' => $fixture['tenant_id'],
            'operation_id' => DB::table('operations')->where('booking_id', $bookingId)->value('id'),
            'from_status' => 'pending',
            'to_status' => 'confirmed',
            'changed_by' => null,
            'actor_type' => 'api_key',
            'actor_identifier' => null,
        ]);
    }

    public function test_api_key_scopes_reject_access_after_revocation_and_expiry(): void
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'name' => 'Revoked API Tenant',
            'status' => true,
            'api_key_prefix' => substr($this->apiKey, 0, 24),
            'api_key_hash' => hash('sha256', $this->apiKey),
            'api_key_expires_at' => today()->addDay()->toDateString(),
            'api_key_scopes' => json_encode(['trips:read']),
        ]);

        $this->withToken($this->apiKey)->getJson('/api/v1/trips')->assertOk();
        DB::table('tenants')->where('id', $tenantId)->update([
            'api_key_prefix' => null,
            'api_key_hash' => null,
        ]);
        $this->withToken($this->apiKey)->getJson('/api/v1/trips')->assertUnauthorized();
    }

    public function test_signed_payment_webhook_is_idempotent_and_updates_booking_and_refund_history(): void
    {
        $fixture = $this->seedBookingFixture(['bookings:create', 'bookings:manage']);
        $booking = $this->withToken($this->apiKey)->postJson('/api/v1/bookings', $this->bookingPayload($fixture))
            ->assertCreated()
            ->json('data.booking_id');
        $operationId = DB::table('operations')->where('booking_id', $booking)->value('id');
        DB::table('payments')->insert([
            'tenant_id' => $fixture['tenant_id'],
            'operation_id' => $operationId,
            'provider' => 'generic',
            'amount' => 200,
            'amount_refunded' => 0,
            'currency_code' => 'EGP',
            'status' => 'pending',
            'transaction_reference' => 'provider-txn-1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        config()->set('services.marinax_payments.webhook_secrets.generic', 'test-webhook-secret');

        $success = [
            'event_id' => 'evt-success-1',
            'type' => 'payment.succeeded',
            'transaction_reference' => 'provider-txn-1',
            'amount' => 200,
        ];
        $this->sendSignedWebhook($success)->assertOk()->assertJsonPath('duplicate', false);
        $this->sendSignedWebhook($success)->assertOk()->assertJsonPath('duplicate', true);
        $this->assertSame('paid', DB::table('operations')->where('id', $operationId)->value('booking_status'));
        $this->assertSame('paid', DB::table('operations')->where('id', $operationId)->value('status_payment'));
        $this->assertDatabaseHas('operation_status_history', [
            'operation_id' => $operationId,
            'from_status' => 'pending',
            'to_status' => 'confirmed',
            'actor_type' => 'payment_provider',
        ]);
        $this->assertDatabaseHas('operation_status_history', [
            'operation_id' => $operationId,
            'from_status' => 'confirmed',
            'to_status' => 'paid',
            'actor_type' => 'payment_provider',
        ]);

        $refund = [
            'event_id' => 'evt-refund-1',
            'type' => 'payment.refunded',
            'transaction_reference' => 'provider-txn-1',
            'amount' => 200,
        ];
        $this->sendSignedWebhook($refund)->assertOk()->assertJsonPath('duplicate', false);
        $this->assertSame('refunded', DB::table('operations')->where('id', $operationId)->value('booking_status'));
        $this->assertDatabaseHas('payments', [
            'transaction_reference' => 'provider-txn-1',
            'status' => 'refunded',
            'amount_refunded' => 200,
        ]);
        $this->assertDatabaseHas('payment_refunds', [
            'payment_id' => DB::table('payments')->where('transaction_reference', 'provider-txn-1')->value('id'),
            'status' => 'succeeded',
            'amount' => 200,
        ]);
        $this->assertSame(2, DB::table('payment_webhook_events')->count());
    }

    public function test_payment_webhook_rejects_bad_signatures_and_reused_event_ids_with_changed_payload(): void
    {
        config()->set('services.marinax_payments.webhook_secrets.generic', 'test-webhook-secret');
        $payload = [
            'event_id' => 'evt-missing-payment',
            'type' => 'payment.failed',
            'transaction_reference' => 'no-such-reference',
            'amount' => 1,
        ];
        $this->postJson('/api/webhooks/payments/generic', $payload, ['X-Payment-Signature' => 'invalid'])
            ->assertUnauthorized();

        $fixture = $this->seedBookingFixture();
        $booking = $this->withToken($this->apiKey)->postJson('/api/v1/bookings', $this->bookingPayload($fixture))
            ->assertCreated()
            ->json('data.booking_id');
        $operationId = DB::table('operations')->where('booking_id', $booking)->value('id');
        DB::table('payments')->insert([
            'tenant_id' => $fixture['tenant_id'],
            'operation_id' => $operationId,
            'provider' => 'generic',
            'amount' => 1,
            'currency_code' => 'EGP',
            'status' => 'pending',
            'transaction_reference' => 'provider-txn-2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $payload['transaction_reference'] = 'provider-txn-2';
        $this->sendSignedWebhook($payload)->assertOk();
        $payload['amount'] = 2;
        $this->sendSignedWebhook($payload)->assertStatus(409);
    }

    public function test_payment_intent_is_not_claimed_or_created_without_a_provider(): void
    {
        $fixture = $this->seedBookingFixture(['bookings:create', 'payments:create']);
        $booking = $this->withToken($this->apiKey)->postJson('/api/v1/bookings', $this->bookingPayload($fixture))
            ->assertCreated()
            ->json('data.booking_id');

        $this->withToken($this->apiKey)
            ->postJson('/api/v1/bookings/'.$booking.'/payments', ['amount' => 100], ['Idempotency-Key' => 'payment-req-001'])
            ->assertStatus(503);
        $this->assertSame(0, DB::table('payments')->count());
    }

    public function test_refund_reservations_are_idempotent_and_prevent_over_refunding(): void
    {
        config()->set('services.marinax_payments.provider', 'generic');
        $fixture = $this->seedBookingFixture(['bookings:create', 'payments:refund']);
        $booking = $this->withToken($this->apiKey)->postJson('/api/v1/bookings', $this->bookingPayload($fixture))
            ->assertCreated()
            ->json('data.booking_id');
        $operationId = DB::table('operations')->where('booking_id', $booking)->value('id');
        $paymentId = DB::table('payments')->insertGetId([
            'tenant_id' => $fixture['tenant_id'],
            'operation_id' => $operationId,
            'provider' => 'generic',
            'amount' => 100,
            'currency_code' => 'EGP',
            'status' => 'succeeded',
            'transaction_reference' => 'refund-txn-1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $gateway = new class implements PaymentGateway
        {
            public int $refundCalls = 0;

            public function createIntent(string $idempotencyKey, int $amountMinor, string $currency, array $metadata): array
            {
                return ['transaction_reference' => 'intent-1', 'redirect_url' => null];
            }

            public function refund(string $transactionReference, string $idempotencyKey, int $amountMinor): void
            {
                $this->refundCalls++;
            }
        };
        app()->instance(PaymentGateway::class, $gateway);

        $this->withToken($this->apiKey)->postJson('/api/v1/payments/'.$paymentId.'/refund', ['amount' => 60], ['Idempotency-Key' => 'refund-req-001'])
            ->assertAccepted();
        $this->withToken($this->apiKey)->postJson('/api/v1/payments/'.$paymentId.'/refund', ['amount' => 60], ['Idempotency-Key' => 'refund-req-001'])
            ->assertAccepted();
        $this->withToken($this->apiKey)->postJson('/api/v1/payments/'.$paymentId.'/refund', ['amount' => 50], ['Idempotency-Key' => 'refund-req-001'])
            ->assertUnprocessable();
        $this->withToken($this->apiKey)->postJson('/api/v1/payments/'.$paymentId.'/refund', ['amount' => 50], ['Idempotency-Key' => 'refund-req-002'])
            ->assertUnprocessable();

        $this->assertSame(2, $gateway->refundCalls);
        $this->assertSame(1, DB::table('payment_refunds')->count());

        $otherApiKey = 'mx_live_'.str_repeat('c', 64);
        $otherTenantId = DB::table('tenants')->insertGetId([
            'name' => 'Other tenant',
            'status' => true,
            'api_key_prefix' => substr($otherApiKey, 0, 24),
            'api_key_hash' => hash('sha256', $otherApiKey),
        ]);
        $otherOperation = (array) DB::table('operations')->where('id', $operationId)->first();
        unset($otherOperation['id']);
        $otherOperation['tenant_id'] = $otherTenantId;
        $otherOperation['booking_id'] = 'FOREIGN-BOOKING';
        $otherOperationId = DB::table('operations')->insertGetId($otherOperation);
        $otherPaymentId = DB::table('payments')->insertGetId([
            'tenant_id' => $otherTenantId,
            'operation_id' => $otherOperationId,
            'provider' => 'generic',
            'amount' => 100,
            'currency_code' => 'EGP',
            'status' => 'succeeded',
            'transaction_reference' => 'foreign-refund-txn',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->withToken($this->apiKey)
            ->postJson('/api/v1/payments/'.$otherPaymentId.'/refund', ['amount' => 50], ['Idempotency-Key' => 'refund-foreign-001'])
            ->assertNotFound();
    }

    private function sendSignedWebhook(array $payload)
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $body, 'test-webhook-secret');

        return $this->call(
            'POST',
            '/api/webhooks/payments/generic',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_PAYMENT_SIGNATURE' => $signature,
            ],
            $body,
        );
    }

    private function seedBookingFixture(?array $scopes = null): array
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'name' => 'Booking Fixture',
            'status' => true,
            'api_key_prefix' => substr($this->apiKey, 0, 24),
            'api_key_hash' => hash('sha256', $this->apiKey),
            'api_key_scopes' => $scopes ? json_encode($scopes) : null,
        ]);
        $regionId = DB::table('regions')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Red Sea',
            'is_active' => true,
        ]);
        $tripId = DB::table('trips')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Fixture trip',
            'region_id' => $regionId,
            'time_trip' => '08:00:00',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $companyId = DB::table('companies')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Fixture company',
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
        DB::table('users')->insert([
            'tenant_id' => $tenantId,
            'name' => 'Fixture Admin',
            'username' => 'fixture-admin',
            'email' => 'fixture@example.test',
            'password' => 'hashed',
            'role' => 'admin',
            'status' => true,
            'is_super_admin' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return compact('tenantId', 'tripId', 'companyId', 'currencyId') + ['tenant_id' => $tenantId];
    }

    private function bookingPayload(array $fixture): array
    {
        return [
            'trip_id' => $fixture['tripId'],
            'company_id' => $fixture['companyId'],
            'currency_id' => $fixture['currencyId'],
            'trip_date' => today()->toDateString(),
            'customer' => ['name' => 'Idempotent Customer', 'phone' => '01000000000'],
            'adults' => 2,
            'children' => 0,
            'infants' => 0,
        ];
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
