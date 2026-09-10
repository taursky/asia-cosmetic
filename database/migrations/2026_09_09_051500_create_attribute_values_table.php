<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('external_id')->nullable()->index();
            $table->uuid('one_c_id')->nullable()->unique();
            $table->decimal('numeric_value', 18, 6)->nullable();
            $table->boolean('boolean_value')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['attribute_id', 'code']);
        });

        Schema::create('attribute_value_lang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete();
            $table->string('lang', 10);
            $table->longText('value');
            $table->timestamps();

            $table->unique(['attribute_value_id', 'lang']);
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            $table->primary(['product_id', 'attribute_value_id']);
        });

        Schema::create('option_attribute_values', function (Blueprint $table) {
            $table->foreignId('option_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            $table->primary(['option_id', 'attribute_value_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option_attribute_values');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('attribute_value_lang');
        Schema::dropIfExists('attribute_values');
    }
};
