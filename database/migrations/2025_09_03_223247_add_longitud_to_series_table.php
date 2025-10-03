<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('series', 'longitud')) {
            Schema::table('series', function (Blueprint $table) {
                $table->unsignedTinyInteger('longitud')
                    ->nullable()
                    ->default(6)
                    ->after('proximo');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('series', 'longitud')) {
            Schema::table('series', function (Blueprint $table) {
                $table->dropColumn('longitud');
            });
        }
    }
};