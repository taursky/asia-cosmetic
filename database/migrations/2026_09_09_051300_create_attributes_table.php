<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type', 32)->default('text');
            $table->string('external_id')->nullable()->index();
            $table->uuid('one_c_id')->nullable()->unique();
            $table->boolean('is_filterable')->default(false)->index();
            $table->boolean('is_variant')->default(false)->index();
            $table->boolean('is_searchable')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
