<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'sync_uid')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->string('sync_uid', 36)->nullable()->after('source')->index();
            });
        }

        if (Schema::hasTable('product_variants') && ! Schema::hasColumn('product_variants', 'source')) {
            Schema::table('product_variants', function (Blueprint $table): void {
                $table->string('source', 100)->nullable()->after('product_id')->index();
            });
        }

        if (Schema::hasTable('warehouses') && ! Schema::hasColumn('warehouses', 'source')) {
            Schema::table('warehouses', function (Blueprint $table): void {
                $table->string('source', 100)->nullable()->after('id')->index();
            });
        }

        // Старые данные считаем данными прежнего источника.
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'source')) {
            DB::table('products')->whereNull('source')->orWhere('source', '')->update(['source' => '1c-unf']);
        }

        if (Schema::hasTable('product_variants') && Schema::hasColumn('product_variants', 'source')) {
            DB::statement(<<<'SQL'
                UPDATE product_variants pv
                INNER JOIN products p ON p.id = pv.product_id
                SET pv.source = COALESCE(NULLIF(p.source, ''), '1c-unf')
                WHERE pv.source IS NULL OR pv.source = ''
            SQL);
        }

        if (Schema::hasTable('warehouses') && Schema::hasColumn('warehouses', 'source')) {
            DB::table('warehouses')->whereNull('source')->orWhere('source', '')->update(['source' => '1c-unf']);
        }

        // Раньше one_c_id был глобально unique. Для нескольких баз 1С идентичность = source + one_c_id.
        $this->dropSingleColumnUnique('products', 'one_c_id');
        $this->dropSingleColumnUnique('product_variants', 'one_c_id');
        $this->dropSingleColumnUnique('warehouses', 'one_c_id');

        $this->addUniqueIfMissing('products', 'products_source_one_c_id_unique', ['source', 'one_c_id']);
        $this->addUniqueIfMissing('product_variants', 'product_variants_source_one_c_id_unique', ['source', 'one_c_id']);
        $this->addUniqueIfMissing('warehouses', 'warehouses_source_one_c_id_unique', ['source', 'one_c_id']);
    }

    public function down(): void
    {
        $this->dropIndexIfExists('products', 'products_source_one_c_id_unique');
        $this->dropIndexIfExists('product_variants', 'product_variants_source_one_c_id_unique');
        $this->dropIndexIfExists('warehouses', 'warehouses_source_one_c_id_unique');

        // Восстанавливаем старые unique(one_c_id) только если после multi-source работы
        // не накопились одинаковые GUID из разных баз.
        $this->restoreSingleUniqueIfPossible('products', 'one_c_id', 'products_one_c_id_unique');
        $this->restoreSingleUniqueIfPossible('product_variants', 'one_c_id', 'product_variants_one_c_id_unique');
        $this->restoreSingleUniqueIfPossible('warehouses', 'one_c_id', 'warehouses_one_c_id_unique');

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'sync_uid')) {
            Schema::table('products', fn (Blueprint $table) => $table->dropColumn('sync_uid'));
        }
        if (Schema::hasTable('product_variants') && Schema::hasColumn('product_variants', 'source')) {
            Schema::table('product_variants', fn (Blueprint $table) => $table->dropColumn('source'));
        }
        if (Schema::hasTable('warehouses') && Schema::hasColumn('warehouses', 'source')) {
            Schema::table('warehouses', fn (Blueprint $table) => $table->dropColumn('source'));
        }
    }

    private function dropSingleColumnUnique(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) return;

        $indexes = $this->indexes($table);
        foreach ($indexes as $name => $meta) {
            if ($name === 'PRIMARY' || ! $meta['unique']) continue;
            if ($meta['columns'] === [$column]) {
                DB::statement(sprintf('ALTER TABLE `%s` DROP INDEX `%s`', $table, $name));
            }
        }
    }

    private function addUniqueIfMissing(string $table, string $name, array $columns): void
    {
        if (! Schema::hasTable($table) || $this->indexExists($table, $name)) return;

        $quoted = implode(', ', array_map(fn ($column) => "`{$column}`", $columns));
        DB::statement("ALTER TABLE `{$table}` ADD UNIQUE INDEX `{$name}` ({$quoted})");
    }

    private function restoreSingleUniqueIfPossible(string $table, string $column, string $name): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column) || $this->indexExists($table, $name)) return;

        $duplicates = DB::table($table)
            ->select($column)
            ->whereNotNull($column)
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if (! $duplicates) {
            DB::statement("ALTER TABLE `{$table}` ADD UNIQUE INDEX `{$name}` (`{$column}`)");
        }
    }

    private function dropIndexIfExists(string $table, string $name): void
    {
        if (Schema::hasTable($table) && $this->indexExists($table, $name)) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
        }
    }

    private function indexExists(string $table, string $name): bool
    {
        return array_key_exists($name, $this->indexes($table));
    }

    private function indexes(string $table): array
    {
        $rows = DB::select("SHOW INDEX FROM `{$table}`");
        $result = [];

        foreach ($rows as $row) {
            $name = $row->Key_name;
            $result[$name] ??= ['unique' => ((int) $row->Non_unique) === 0, 'columns' => []];
            $result[$name]['columns'][(int) $row->Seq_in_index] = $row->Column_name;
        }

        foreach ($result as &$index) {
            ksort($index['columns']);
            $index['columns'] = array_values($index['columns']);
        }

        return $result;
    }
};
