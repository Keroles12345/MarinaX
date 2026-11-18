<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->json('api_key_scopes')->nullable();
        });

        Schema::table('operations', function (Blueprint $table): void {
            $table->string('booking_status', 20)->default('pending')->index();
        });

        Schema::create('tenant_api_idempotency_keys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->char('key_hash', 64);
            $table->char('request_hash', 64);
            $table->json('response_body')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->foreignId('operation_id')->nullable()->constrained('operations')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'key_hash'], 'tenant_api_idempotency_scope_unique');
        });

        Schema::create('operation_status_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operation_id')->constrained('operations')->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_type', 30)->default('system');
            $table->string('actor_identifier', 191)->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'operation_id', 'created_at'], 'operation_status_history_tenant_idx');
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operation_id')->constrained('operations')->cascadeOnDelete();
            $table->string('provider', 80);
            $table->decimal('amount', 10, 2);
            $table->decimal('amount_refunded', 10, 2)->default(0);
            $table->string('currency_code', 10);
            $table->string('status', 30)->default('pending');
            $table->string('transaction_reference', 191)->nullable();
            $table->char('idempotency_key_hash', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('succeeded_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'transaction_reference'], 'payments_transaction_reference_unique');
            $table->unique(['tenant_id', 'idempotency_key_hash'], 'payments_idempotency_key_unique');
            $table->index(['tenant_id', 'operation_id', 'status'], 'payments_booking_status_idx');
        });

        Schema::create('payment_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 80);
            $table->string('event_id', 191);
            $table->char('payload_hash', 64);
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'event_id'], 'payment_webhook_provider_event_unique');
        });

        Schema::create('payment_refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->char('idempotency_key_hash', 64);
            $table->decimal('amount', 10, 2);
            $table->string('status', 30)->default('pending');
            $table->string('transaction_reference', 191)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'idempotency_key_hash'], 'payment_refund_idempotency_unique');
        });

        Schema::create('booking_notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operation_id')->constrained('operations')->cascadeOnDelete();
            $table->string('event_type', 40);
            $table->string('recipient', 255);
            $table->timestamps();
            $table->unique(['tenant_id', 'operation_id', 'event_type', 'recipient'], 'booking_notification_event_unique');
        });

        DB::table('operations')->orderBy('id')->chunkById(500, function ($operations): void {
            foreach ($operations as $operation) {
                $status = in_array($operation->status, ['cancel', 'cancelled'], true)
                    ? 'cancelled'
                    : (in_array($operation->status_payment, ['refunded', 'refund'], true)
                        ? 'refunded'
                        : ($operation->status_payment === 'paid' && (float) $operation->amount_remaining <= 0
                            ? 'paid'
                            : (in_array($operation->status, ['complete', 'confirmed'], true) ? 'confirmed' : 'pending')));

                DB::table('operations')->where('id', $operation->id)->update(['booking_status' => $status]);
                DB::table('operation_status_history')->insert([
                    'tenant_id' => $operation->tenant_id,
                    'operation_id' => $operation->id,
                    'from_status' => null,
                    'to_status' => $status,
                    'changed_by' => null,
                    'actor_type' => 'system',
                    'actor_identifier' => null,
                    'reason' => 'Initial status migrated from existing operation data.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_notification_deliveries');
        Schema::dropIfExists('payment_refunds');
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('operation_status_history');
        Schema::dropIfExists('tenant_api_idempotency_keys');
        Schema::table('operations', function (Blueprint $table): void {
            $table->dropIndex(['booking_status']);
            $table->dropColumn('booking_status');
        });
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn('api_key_scopes');
        });
    }
};
