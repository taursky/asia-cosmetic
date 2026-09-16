<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_roles', function (Blueprint $table): void {
            if (! Schema::hasColumn('customer_roles', 'product_price_type_id')) {
                $table->foreignId('product_price_type_id')->nullable()->constrained('product_price_types')->nullOnDelete();
            }
            if (! Schema::hasColumn('customer_roles', 'level')) {
                $table->unsignedSmallInteger('level')->default(0)->index();
            }
            if (! Schema::hasColumn('customer_roles', 'order_threshold_amount')) {
                $table->decimal('order_threshold_amount', 15, 2)->nullable();
            }
            if (! Schema::hasColumn('customer_roles', 'qualification_amount')) {
                $table->decimal('qualification_amount', 15, 2)->default(0);
            }
            if (! Schema::hasColumn('customer_roles', 'qualification_period_days')) {
                $table->unsignedInteger('qualification_period_days')->default(180);
            }
            if (! Schema::hasColumn('customer_roles', 'validity_days')) {
                $table->unsignedInteger('validity_days')->default(180);
            }
            if (! Schema::hasColumn('customer_roles', 'is_default')) {
                $table->boolean('is_default')->default(false)->index();
            }
            if (! Schema::hasColumn('customer_roles', 'is_auto')) {
                $table->boolean('is_auto')->default(true)->index();
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'customer_role_id')) {
                $table->foreignId('customer_role_id')->nullable()->constrained('customer_roles')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'customer_role_locked')) {
                $table->boolean('customer_role_locked')->default(false)->index();
            }
            if (! Schema::hasColumn('users', 'customer_role_valid_until')) {
                $table->timestamp('customer_role_valid_until')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        // Откат намеренно не автоматизируем: миграция расширяет уже используемые бизнес-данные.
    }
};
