<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('static_page_lang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('static_page_id')->constrained('static_pages')->cascadeOnDelete();
            $table->string('lang', 6);
            $table->string('title');
            $table->string('slug')->nullable()->comment('Localized slug');
            $table->longText('content')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->timestamps();
            $table->unique(['static_page_id', 'lang', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('static_page_lang');
    }
};
