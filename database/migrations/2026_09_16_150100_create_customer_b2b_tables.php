<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('legal_type', 32)->default('individual')->index();
            $table->string('company_name')->nullable();
            $table->string('full_company_name')->nullable();
            $table->string('inn', 12)->nullable()->index();
            $table->string('kpp', 9)->nullable();
            $table->string('ogrn', 15)->nullable();
            $table->string('ogrnip', 15)->nullable();
            $table->string('legal_address')->nullable();
            $table->string('actual_address')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_bik', 9)->nullable();
            $table->string('bank_account', 20)->nullable();
            $table->string('bank_corr_account', 20)->nullable();
            $table->string('director_name')->nullable();
            $table->string('director_position')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('verification_status', 24)->default('draft')->index();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_comment')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_role_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_role_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 32)->index();
            $table->decimal('qualified_amount', 15, 2)->nullable();
            $table->unsignedInteger('qualification_period_days')->nullable();
            $table->timestamp('valid_from');
            $table->timestamp('valid_until')->nullable();
            $table->foreignId('changed_by_admin_user_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'valid_from']);
        });

        Schema::create('customer_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32)->index();
            $table->string('title');
            $table->string('disk')->default('private');
            $table->string('path');
            $table->string('external_id')->nullable()->index();
            $table->timestamp('available_from')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 24)->default('requested')->index();
            $table->text('comment')->nullable();
            $table->string('external_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('orders', 'customer_role_id')) {
                $table->foreignId('customer_role_id')->nullable()->constrained('customer_roles')->nullOnDelete();
            }
            if (! Schema::hasColumn('orders', 'product_price_type_id')) {
                $table->foreignId('product_price_type_id')->nullable()->constrained('product_price_types')->nullOnDelete();
            }
            if (! Schema::hasColumn('orders', 'pricing_meta')) {
                $table->json('pricing_meta')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_requests');
        Schema::dropIfExists('customer_documents');
        Schema::dropIfExists('customer_role_history');
        Schema::dropIfExists('customer_profiles');
    }
};
