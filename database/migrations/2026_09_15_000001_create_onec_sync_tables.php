<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('direction', 32)->index();
            $table->string('entity_type', 64)->index();
            $table->string('entity_id')->nullable()->index();
            $table->string('external_id')->nullable()->index();
            $table->string('exchange_id', 100)->nullable()->index();
            $table->string('status', 32)->index();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error')->nullable();
            $table->unsignedInteger('attempts')->default(1);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sync_states', function (Blueprint $table): void {
            $table->id();
            $table->string('channel')->unique();
            $table->timestamp('last_success_at')->nullable();
            $table->string('last_exchange_id', 100)->nullable();
            $table->string('last_cursor')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_states');
        Schema::dropIfExists('sync_logs');
    }
};
