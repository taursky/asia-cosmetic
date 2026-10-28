<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE product_lang ADD FULLTEXT INDEX product_lang_search_fulltext (name, short_description, description)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE product_lang DROP INDEX product_lang_search_fulltext');
    }
};
