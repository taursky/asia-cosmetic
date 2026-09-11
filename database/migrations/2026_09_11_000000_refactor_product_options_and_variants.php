<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Existing `options` table contains real SKU / product variants.
         * Before renaming tables and columns we MUST drop all foreign keys
         * whose names contain `option...`, because MySQL keeps constraint
         * names after RENAME TABLE and FK names must be unique per schema.
         */

        Schema::table('option_lang', function (Blueprint $table): void {
            $table->dropForeign('option_lang_option_id_foreign');
        });

        Schema::table('option_attribute_values', function (Blueprint $table): void {
            $table->dropForeign('option_attribute_values_option_id_foreign');
            $table->dropForeign('option_attribute_values_attribute_value_id_foreign');
        });

        Schema::table('product_prices', function (Blueprint $table): void {
            $table->dropForeign('product_prices_option_id_foreign');
        });

        Schema::table('options', function (Blueprint $table): void {
            $table->dropForeign('options_product_id_foreign');
        });

        /*
         * Rename existing SKU structures.
         */
        Schema::rename('options', 'product_variants');
        Schema::rename('option_lang', 'product_variant_lang');
        Schema::rename('option_attribute_values', 'product_variant_attribute_values');

        Schema::table('product_variant_lang', function (Blueprint $table): void {
            $table->renameColumn('option_id', 'product_variant_id');
        });

        Schema::table('product_variant_attribute_values', function (Blueprint $table): void {
            $table->renameColumn('option_id', 'product_variant_id');
        });

        Schema::table('product_prices', function (Blueprint $table): void {
            $table->renameColumn('option_id', 'product_variant_id');
        });

        /*
         * Recreate foreign keys with names matching the new SKU structure.
         */
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->foreign('product_id', 'product_variants_product_id_foreign')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();
        });

        Schema::table('product_variant_lang', function (Blueprint $table): void {
            $table->foreign('product_variant_id', 'product_variant_lang_product_variant_id_foreign')
                ->references('id')
                ->on('product_variants')
                ->cascadeOnDelete();
        });

        Schema::table('product_variant_attribute_values', function (Blueprint $table): void {
            $table->foreign('product_variant_id', 'product_variant_attribute_values_product_variant_id_foreign')
                ->references('id')
                ->on('product_variants')
                ->cascadeOnDelete();

            $table->foreign('attribute_value_id', 'product_variant_attribute_values_attribute_value_id_foreign')
                ->references('id')
                ->on('attribute_values')
                ->cascadeOnDelete();
        });

        Schema::table('product_prices', function (Blueprint $table): void {
            $table->foreign('product_variant_id', 'product_prices_product_variant_id_foreign')
                ->references('id')
                ->on('product_variants')
                ->cascadeOnDelete();
        });

        /*
         * Images are polymorphic. Existing SKU images pointed to App\Models\Option;
         * after the rename they must point to App\Models\ProductVariant.
         */
        DB::table('images')
            ->where('imageable_type', 'App\\Models\\Option')
            ->update(['imageable_type' => 'App\\Models\\ProductVariant']);

        /*
         * New option dictionary: Color, Size, Volume, etc.
         */
        Schema::create('options', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('external_id')->nullable()->index();
            $table->uuid('one_c_id')->nullable()->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('option_lang', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('option_id')
                ->constrained('options')
                ->cascadeOnDelete();
            $table->string('lang', 10);
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['option_id', 'lang']);
        });

        Schema::create('option_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('option_id')
                ->constrained('options')
                ->cascadeOnDelete();
            $table->string('code');
            $table->string('external_id')->nullable()->index();
            $table->uuid('one_c_id')->nullable()->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['option_id', 'code']);
        });

        Schema::create('option_value_lang', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('option_value_id')
                ->constrained('option_values')
                ->cascadeOnDelete();
            $table->string('lang', 10);
            $table->string('value');
            $table->timestamps();

            $table->unique(['option_value_id', 'lang']);
        });

        /*
         * Values available for a product.
         * Example: Product X can have Color: Red / Black and Volume: 50 / 100 ml.
         */
        Schema::create('product_option_values', function (Blueprint $table): void {
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->foreignId('option_value_id')
                ->constrained('option_values')
                ->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            $table->primary(['product_id', 'option_value_id']);
        });

        /*
         * Exact option values selected by a concrete SKU / variant.
         */
        Schema::create('product_variant_option_values', function (Blueprint $table): void {
            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();
            $table->foreignId('option_value_id')
                ->constrained('option_values')
                ->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            $table->primary(['product_variant_id', 'option_value_id']);
        });
    }

    public function down(): void
    {
        /*
         * Remove the new selectable-option structures first.
         */
        Schema::dropIfExists('product_variant_option_values');
        Schema::dropIfExists('product_option_values');
        Schema::dropIfExists('option_value_lang');
        Schema::dropIfExists('option_values');
        Schema::dropIfExists('option_lang');
        Schema::dropIfExists('options');

        /*
         * Restore polymorphic image model name.
         */
        DB::table('images')
            ->where('imageable_type', 'App\\Models\\ProductVariant')
            ->update(['imageable_type' => 'App\\Models\\Option']);

        /*
         * Drop FK constraints created for the renamed SKU structures before
         * returning tables/columns to their original names.
         */
        Schema::table('product_prices', function (Blueprint $table): void {
            $table->dropForeign('product_prices_product_variant_id_foreign');
        });

        Schema::table('product_variant_attribute_values', function (Blueprint $table): void {
            $table->dropForeign('product_variant_attribute_values_product_variant_id_foreign');
            $table->dropForeign('product_variant_attribute_values_attribute_value_id_foreign');
        });

        Schema::table('product_variant_lang', function (Blueprint $table): void {
            $table->dropForeign('product_variant_lang_product_variant_id_foreign');
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropForeign('product_variants_product_id_foreign');
        });

        /*
         * Restore original column names.
         */
        Schema::table('product_prices', function (Blueprint $table): void {
            $table->renameColumn('product_variant_id', 'option_id');
        });

        Schema::table('product_variant_attribute_values', function (Blueprint $table): void {
            $table->renameColumn('product_variant_id', 'option_id');
        });

        Schema::table('product_variant_lang', function (Blueprint $table): void {
            $table->renameColumn('product_variant_id', 'option_id');
        });

        /*
         * Restore original table names.
         */
        Schema::rename('product_variant_attribute_values', 'option_attribute_values');
        Schema::rename('product_variant_lang', 'option_lang');
        Schema::rename('product_variants', 'options');

        /*
         * Restore original FK names exactly as they were in the backup.
         */
        Schema::table('options', function (Blueprint $table): void {
            $table->foreign('product_id', 'options_product_id_foreign')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();
        });

        Schema::table('option_lang', function (Blueprint $table): void {
            $table->foreign('option_id', 'option_lang_option_id_foreign')
                ->references('id')
                ->on('options')
                ->cascadeOnDelete();
        });

        Schema::table('option_attribute_values', function (Blueprint $table): void {
            $table->foreign('option_id', 'option_attribute_values_option_id_foreign')
                ->references('id')
                ->on('options')
                ->cascadeOnDelete();

            $table->foreign('attribute_value_id', 'option_attribute_values_attribute_value_id_foreign')
                ->references('id')
                ->on('attribute_values')
                ->cascadeOnDelete();
        });

        Schema::table('product_prices', function (Blueprint $table): void {
            $table->foreign('option_id', 'product_prices_option_id_foreign')
                ->references('id')
                ->on('options')
                ->cascadeOnDelete();
        });
    }
};
