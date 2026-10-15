<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('customer_type', 16)->after('total')->nullable()->index(); // individual | legal
            $table->string('payment_method', 24)->after('delivery_data')->nullable(); // card | invoice
            $table->uuid('checkout_session_uuid')->after('payment_data')->nullable()->unique();
            $table->unsignedInteger('revision')->after('payment_data')->default(1);
        });

        Schema::create('checkout_sessions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('status', 32)->default('pending_payment')->index();
            $table->string('customer_type', 16)->default('individual');
            $table->string('payment_method', 24)->default('card');
            $table->string('currency', 3)->default('RUB');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('delivery_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->json('customer_data')->nullable();
            $table->json('delivery_data')->nullable();
            $table->json('pricing_snapshot')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('checkout_session_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('checkout_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->index();
            $table->foreignId('product_variant_id')->nullable()->index();
            $table->uuid('product_one_c_id')->nullable();
            $table->uuid('variant_one_c_id')->nullable();
            $table->string('sku');
            $table->string('name');
            $table->decimal('quantity', 14, 3);
            $table->decimal('price', 15, 2);
            $table->decimal('total', 15, 2);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('checkout_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 40); // mock | acquiring provider | 1c
            $table->string('type', 24)->default('payment'); // payment | refund
            $table->string('status', 32)->default('pending')->index();
            $table->string('external_id', 191)->nullable();
            $table->string('idempotency_key', 191)->unique();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('RUB');
            $table->json('provider_payload')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'external_id']);
        });

        Schema::create('payment_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 40);
            $table->string('external_event_id', 191)->nullable();
            $table->string('payload_hash', 64);
            $table->string('event_type', 80)->nullable();
            $table->string('processing_status', 24)->default('received')->index();
            $table->json('payload');
            $table->text('error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'external_event_id']);
            $table->unique(['provider', 'payload_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('checkout_session_items');
        Schema::dropIfExists('checkout_sessions');

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['customer_type', 'payment_method', 'checkout_session_uuid', 'revision']);
        });
    }
};
