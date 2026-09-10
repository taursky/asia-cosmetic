<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_price_type_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('old_amount', 15, 2)->nullable();
            $table->char('currency', 3)->default('RUB');
            $table->decimal('min_quantity', 14, 3)->default(1);
            $table->string('external_id')->nullable()->index();
            $table->uuid('one_c_id')->nullable()->unique();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'product_price_type_id']);
            $table->index(['option_id', 'product_price_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
