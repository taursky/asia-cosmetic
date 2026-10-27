<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_review_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_review_id')->constrained('product_reviews')->cascadeOnDelete();
            $table->string('path');
            $table->string('type', 10);
            $table->string('mime_type', 100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('product_review_media'); }
};
