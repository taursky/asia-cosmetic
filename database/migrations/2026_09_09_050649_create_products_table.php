<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->nullable()->index();
            $table->uuid('one_c_id')->nullable()->unique();
            $table->string('sku')->nullable()->index();
            $table->string('source')->nullable()->index();
            $table->text('source_url')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_visible')->default(true)->index();
            $table->boolean('allow_discounts')->default(true);
            $table->decimal('vat_rate', 5, 2)->nullable();
            $table->string('unit', 32)->nullable();
            $table->decimal('weight', 12, 3)->nullable();
            $table->decimal('length', 12, 3)->nullable();
            $table->decimal('width', 12, 3)->nullable();
            $table->decimal('height', 12, 3)->nullable();
            $table->text('video_url')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->string('sync_hash', 64)->nullable()->index();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['source', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
