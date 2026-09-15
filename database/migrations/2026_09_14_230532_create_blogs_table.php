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
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(false);
            $table->unsignedInteger('views_count')->default(0);
            $table->longText('product_ids')->nullable();
            $table->timestamps();
        });

        Schema::create('blog_lang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_id')->constrained('blogs')->onDelete('cascade');
            $table->string('lang', 4)->index();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('slug', 255)->unique();
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_lang');
        Schema::dropIfExists('blogs');
    }
};
