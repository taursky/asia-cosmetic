<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalog_collections', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('filter', 30)->default('all');
            $table->string('sort', 30)->default('new');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->unsignedSmallInteger('days')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('show_in_menu')->default(false);
            $table->boolean('show_on_home')->default(false);
            $table->unsignedInteger('sort_order')->default(100);
            $table->timestamps();
        });

        Schema::create('catalog_collection_product', function (Blueprint $table): void {
            $table->foreignId('catalog_collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->primary(['catalog_collection_id', 'product_id'], 'catalog_collection_product_pk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_collection_product');
        Schema::dropIfExists('catalog_collections');
    }
};
