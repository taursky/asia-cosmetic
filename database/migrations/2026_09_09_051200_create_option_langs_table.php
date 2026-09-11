<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('option_lang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_id')->constrained('options')->cascadeOnDelete();
            $table->string('lang', 10);
            $table->string('name')->nullable();
            $table->text('value')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['option_id', 'lang']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option_lang');
    }
};
