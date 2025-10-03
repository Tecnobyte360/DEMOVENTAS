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
        Schema::table('inventario_ruta', function (Blueprint $table) {
            if (!Schema::hasColumn('inventario_ruta', 'cantidad_inicial')) {
                $table->integer('cantidad_inicial')
                      ->default(0)
                      ->after('cantidad');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventario_ruta', function (Blueprint $table) {
            if (Schema::hasColumn('inventario_ruta', 'cantidad_inicial')) {
                $table->dropColumn('cantidad_inicial');
            }
        });
    }
};
