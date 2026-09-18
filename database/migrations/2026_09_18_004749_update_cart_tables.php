<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('carts', 'currency')) {
            Schema::table('carts', function (Blueprint $table): void {
                $table->string('currency', 3)
                    ->default('RUB')
                    ->after('user_id');
            });
        }

        if (! Schema::hasColumn('cart_items', 'product_id')) {
            Schema::table('cart_items', function (Blueprint $table): void {
                $table->unsignedBigInteger('product_id')
                    ->nullable()
                    ->after('cart_id');
            });

            // Заполняем product_id существующих позиций по ProductVariant.
            DB::statement('
                UPDATE cart_items ci
                INNER JOIN product_variants pv
                    ON pv.id = ci.product_variant_id
                SET ci.product_id = pv.product_id
                WHERE ci.product_id IS NULL
            ');

            DB::table('cart_items')
                ->whereNull('product_id')
                ->delete();

            Schema::table('cart_items', function (Blueprint $table): void {
                $table->unsignedBigInteger('product_id')
                    ->nullable(false)
                    ->change();

                $table->foreign('product_id', 'cart_items_product_id_foreign')
                    ->references('id')
                    ->on('products')
                    ->cascadeOnDelete();
            });
        }

        if (! $this->indexExists('cart_items', 'cart_items_cart_id_product_variant_id_unique')) {
            // На случай старых дублей сначала объединяем количество.
            $duplicates = DB::table('cart_items')
                ->select(
                    'cart_id',
                    'product_variant_id',
                    DB::raw('MIN(id) as keep_id'),
                    DB::raw('SUM(quantity) as total_quantity'),
                    DB::raw('COUNT(*) as rows_count'),
                )
                ->groupBy('cart_id', 'product_variant_id')
                ->having('rows_count', '>', 1)
                ->get();

            foreach ($duplicates as $duplicate) {
                DB::table('cart_items')
                    ->where('id', $duplicate->keep_id)
                    ->update([
                        'quantity' => $duplicate->total_quantity,
                        'updated_at' => now(),
                    ]);

                DB::table('cart_items')
                    ->where('cart_id', $duplicate->cart_id)
                    ->where('product_variant_id', $duplicate->product_variant_id)
                    ->where('id', '<>', $duplicate->keep_id)
                    ->delete();
            }

            Schema::table('cart_items', function (Blueprint $table): void {
                $table->unique(
                    ['cart_id', 'product_variant_id'],
                    'cart_items_cart_id_product_variant_id_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (! $this->indexExists('cart_items', 'cart_items_cart_id_index')) {
            Schema::table('cart_items', function (Blueprint $table): void {
                $table->index('cart_id', 'cart_items_cart_id_index');
            });
        }

        if (! $this->indexExists('cart_items', 'cart_items_product_variant_id_index')) {
            Schema::table('cart_items', function (Blueprint $table): void {
                $table->index('product_variant_id', 'cart_items_product_variant_id_index');
            });
        }

        if ($this->indexExists('cart_items', 'cart_items_cart_id_product_variant_id_unique')) {
            Schema::table('cart_items', function (Blueprint $table): void {
                $table->dropUnique('cart_items_cart_id_product_variant_id_unique');
            });
        }

        if (Schema::hasColumn('cart_items', 'product_id')) {
            Schema::table('cart_items', function (Blueprint $table): void {
                if ($this->foreignKeyExists('cart_items', 'cart_items_product_id_foreign')) {
                    $table->dropForeign('cart_items_product_id_foreign');
                }

                $table->dropColumn('product_id');
            });
        }

        if (Schema::hasColumn('carts', 'currency')) {
            Schema::table('carts', function (Blueprint $table): void {
                $table->dropColumn('currency');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('constraint_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('constraint_name', $constraint)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
