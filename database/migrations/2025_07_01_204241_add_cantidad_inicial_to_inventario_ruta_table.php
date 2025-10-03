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
        $table->integer('cantidad_inicial')->default(0)->after('cantidad');
    });
}

public function down(): void
{
    Schema::table('inventario_ruta', function (Blueprint $table) {
        $table->dropColumn('cantidad_inicial');
    });
}
};
