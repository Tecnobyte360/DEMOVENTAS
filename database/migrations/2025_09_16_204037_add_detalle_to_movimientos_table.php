<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Si usas un único esquema por defecto:
        Schema::connection('sqlsrv')->table('movimientos', function (Blueprint $table) {
            if (!Schema::connection('sqlsrv')->hasColumn('movimientos', 'detalle')) {
                $table->text('detalle')->nullable(); // NVARCHAR(MAX) en SQL Server
            }
            if (!Schema::connection('sqlsrv')->hasColumn('movimientos', 'debe')) {
                $table->decimal('debe', 18, 2)->default(0);
            }
            if (!Schema::connection('sqlsrv')->hasColumn('movimientos', 'haber')) {
                $table->decimal('haber', 18, 2)->default(0);
            }
        });

        // 🔸 Si tu tabla está en esquema 'admin', usa SQL directo (descomenta y ajusta):
        /*
        DB::statement("
            IF COL_LENGTH('admin.movimientos','detalle') IS NULL
                ALTER TABLE [admin].[movimientos] ADD [detalle] NVARCHAR(MAX) NULL;
            IF COL_LENGTH('admin.movimientos','debe') IS NULL
                ALTER TABLE [admin].[movimientos] ADD [debe] DECIMAL(18,2) NOT NULL CONSTRAINT DF_mov_debe DEFAULT(0);
            IF COL_LENGTH('admin.movimientos','haber') IS NULL
                ALTER TABLE [admin].[movimientos] ADD [haber] DECIMAL(18,2) NOT NULL CONSTRAINT DF_mov_haber DEFAULT(0);
        ");
        */
    }

    public function down(): void
    {
        Schema::connection('sqlsrv')->table('movimientos', function (Blueprint $table) {
            if (Schema::connection('sqlsrv')->hasColumn('movimientos', 'detalle')) {
                $table->dropColumn('detalle');
            }
            // Quitar 'debe' y 'haber' sólo si lo necesitas
            // if (Schema::connection('sqlsrv')->hasColumn('movimientos', 'debe')) $table->dropColumn('debe');
            // if (Schema::connection('sqlsrv')->hasColumn('movimientos', 'haber')) $table->dropColumn('haber');
        });

        // Para esquema 'admin', análogo con DB::statement(...)
    }
};
