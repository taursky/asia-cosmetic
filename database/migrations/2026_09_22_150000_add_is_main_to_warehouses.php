<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('warehouses', 'is_main')) {
            Schema::table('warehouses', function (Blueprint $table): void {
                $table->boolean('is_main')
                    ->default(false)
                    ->after('is_active')
                    ->index();
            });
        }

        // Если в базе уже есть MAIN / «Основной склад», назначаем его основным.
        $mainId = DB::table('warehouses')
            ->where('is_active', true)
            ->where(function ($query): void {
                $query
                    ->whereRaw('LOWER(code) = ?', ['main'])
                    ->orWhere('name', 'Основной склад');
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('id');

        // Если такого склада нет, но склады уже существуют — основной будет первый активный.
        $mainId ??= DB::table('warehouses')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('id');

        if ($mainId) {
            DB::table('warehouses')->update(['is_main' => false]);
            DB::table('warehouses')->where('id', $mainId)->update(['is_main' => true]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('warehouses', 'is_main')) {
            Schema::table('warehouses', function (Blueprint $table): void {
                $table->dropIndex(['is_main']);
                $table->dropColumn('is_main');
            });
        }
    }
};
