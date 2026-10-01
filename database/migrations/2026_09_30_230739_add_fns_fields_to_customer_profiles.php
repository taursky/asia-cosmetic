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
        Schema::table('customer_profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('customer_profiles', 'fns_status')) {
                $table->string('fns_status', 64)->nullable()->after('verification_comment')->index();
            }
            if (! Schema::hasColumn('customer_profiles', 'fns_checked_at')) {
                $table->timestamp('fns_checked_at')->nullable()->after('fns_status');
            }
            if (! Schema::hasColumn('customer_profiles', 'fns_data')) {
                $table->json('fns_data')->nullable()->after('fns_checked_at');
            }
            if (! Schema::hasColumn('customer_profiles', 'fns_check_data')) {
                $table->json('fns_check_data')->nullable()->after('fns_data');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table): void {
            foreach (['fns_check_data', 'fns_data', 'fns_checked_at', 'fns_status'] as $column) {
                if (Schema::hasColumn('customer_profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
