<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_lang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('lang', 10);
            $table->string('name');
            $table->string('unit')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['attribute_id', 'lang']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_lang');
    }
};
