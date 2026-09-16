<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table): void {
            $table->id();
            $table->string('external_id')->nullable()->index();
            $table->uuid('one_c_id')->nullable()->unique();
            $table->string('code')->nullable()->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('synced_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('product_variant_stocks', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnDelete();

            $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('reserved', 15, 3)->default(0);
            $table->decimal('available', 15, 3)->default(0);
            $table->timestamp('synced_at')->nullable()->index();
            $table->timestamps();

            $table->unique(
                ['product_variant_id', 'warehouse_id'],
                'product_variant_stocks_variant_warehouse_unique'
            );

            $table->index(['warehouse_id', 'available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_stocks');
        Schema::dropIfExists('warehouses');
    }
};
