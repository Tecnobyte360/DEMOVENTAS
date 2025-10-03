<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('series', function (Blueprint $table) {
            if (!Schema::hasColumn('series', 'documento')) {
                $table->string('documento', 40)->default('factura')->after('prefijo');
            }
            if (!Schema::hasColumn('series', 'es_default')) {
                $table->boolean('es_default')->default(false)->after('documento');
            }
        });
    }

    public function down(): void
    {
        Schema::table('series', function (Blueprint $table) {
            if (Schema::hasColumn('series', 'es_default')) {
                $table->dropColumn('es_default');
            }
            if (Schema::hasColumn('series', 'documento')) {
                $table->dropColumn('documento');
            }
        });
    }
};
