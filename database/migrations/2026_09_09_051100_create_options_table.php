<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('external_id')->nullable()->index();
            $table->uuid('one_c_id')->nullable()->unique();
            $table->string('sku')->nullable()->index();
            $table->string('barcode')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->decimal('stock', 14, 3)->default(0);
            $table->decimal('weight', 12, 3)->nullable();
            $table->decimal('length', 12, 3)->nullable();
            $table->decimal('width', 12, 3)->nullable();
            $table->decimal('height', 12, 3)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('sync_hash', 64)->nullable()->index();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('options');
    }
};
