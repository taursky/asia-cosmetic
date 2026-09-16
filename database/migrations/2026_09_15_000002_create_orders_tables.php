<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('number')->unique();
            $table->uuid('one_c_id')->nullable()->unique();
            $table->string('one_c_number')->nullable();
            $table->string('status', 50)->default('new')->index();
            $table->string('payment_status', 50)->default('pending')->index();
            $table->string('currency', 3)->default('RUB');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('delivery_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->json('customer_data')->nullable();
            $table->json('delivery_data')->nullable();
            $table->json('payment_data')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('sync_status', 32)->default('pending')->index();
            $table->text('sync_error')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->uuid('product_one_c_id')->nullable()->index();
            $table->uuid('variant_one_c_id')->nullable()->index();
            $table->string('sku')->nullable()->index();
            $table->string('name');
            $table->decimal('quantity', 14, 3);
            $table->decimal('price', 15, 2);
            $table->decimal('total', 15, 2);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
