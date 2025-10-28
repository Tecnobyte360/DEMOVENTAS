<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Agregar la columna y la FK (NULL ON DELETE para compatibilidad universal)
        Schema::table('series', function (Blueprint $table) {
            if (!Schema::hasColumn('series', 'tipo_documento_id')) {
                $table->foreignId('tipo_documento_id')
                    ->nullable()                  // requerido para nullOnDelete()
                    ->after('prefijo')
                    ->constrained('tipo_documentos')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();            // ✅ MySQL / PG / SQL Server / SQLite
            }
        });

        // 2) Backfill: mapear 'series.documento' -> 'tipo_documentos.codigo'
        if (Schema::hasColumn('series', 'documento')) {
            // [codigo => id]
            $map = DB::table('tipo_documentos')->pluck('id', 'codigo');

            // recorrer en cursor para no cargar todo en memoria
            foreach (DB::table('series')->select('id', 'documento')->cursor() as $row) {
                $codigo = $row->documento ?: 'factura';
                $tipoId = $map[$codigo] ?? ($map['otro'] ?? null);
                DB::table('series')
                    ->where('id', $row->id)
                    ->update(['tipo_documento_id' => $tipoId]);
            }
        }
    }

    public function down(): void
    {
        // Soltar FK y columna (independiente del motor)
        Schema::table('series', function (Blueprint $table) {
            if (Schema::hasColumn('series', 'tipo_documento_id')) {
                // Nombre implícito: series_tipo_documento_id_foreign
                try { $table->dropForeign(['tipo_documento_id']); } catch (\Throwable $e) {}
                $table->dropColumn('tipo_documento_id');
            }
        });
    }
};
