<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->uuid('sync_uid')
                ->nullable()
                ->after('one_c_id');

            $table->unique('sync_uid');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['sync_uid']);
            $table->dropColumn('sync_uid');
        });
    }
};
