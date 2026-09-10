<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->integer('position')->default(1);
            $table->unsignedBigInteger('imageable_id')->index();
            $table->string('imageable_type');
            $table->string('name');
            $table->string('mime_type')->nullable();
            $table->boolean('is_primary')->default(false)->index();
            $table->timestamps();


        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
