<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nota_creditos', function (Blueprint $table) {
            if (!Schema::hasColumn('nota_creditos', 'motivo')) {
                $table->string('motivo', 120)->nullable()->after('notas');
            }
            if (!Schema::hasColumn('nota_creditos', 'reponer_inventario')) {
                $table->boolean('reponer_inventario')->default(false)->after('motivo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nota_creditos', function (Blueprint $table) {
            if (Schema::hasColumn('nota_creditos', 'reponer_inventario')) {
                $table->dropColumn('reponer_inventario');
            }
            if (Schema::hasColumn('nota_creditos', 'motivo')) {
                $table->dropColumn('motivo');
            }
        });
    }
};
