<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('asientos', function (Blueprint $table) {
            // crea la columna si no existe
            if (!Schema::hasColumn('asientos', 'tercero_id')) {
                $table->unsignedBigInteger('tercero_id')->nullable()->after('origen_id');
            }

            // crea la FK si no existe (en SQL Server el nombre importa)
            $fkName = 'asientos_tercero_id_foreign';
            // para SQL Server, usar try/catch no es necesario; basta con recrearla correctamente:
            $table->foreign('tercero_id', $fkName)
                  ->references('id')
                  ->on('socio_negocios')   
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('asientos', function (Blueprint $table) {
            // soltar FK si existe
            try { $table->dropForeign('asientos_tercero_id_foreign'); } catch (\Throwable $e) {}
            if (Schema::hasColumn('asientos', 'tercero_id')) {
                $table->dropColumn('tercero_id');
            }
        });
    }
};
