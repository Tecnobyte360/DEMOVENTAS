<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ===== ENTRADAS MERCANCIA: agregar concepto =====
        Schema::table('entradas_mercancia', function (Blueprint $table) {
            // Evita duplicar columna si ya existe
            if (!Schema::hasColumn('entradas_mercancia', 'concepto_documento_id')) {
                $table->foreignId('concepto_documento_id')
                    ->nullable()
                    ->after('socio_negocio_id')
                    ->constrained('conceptos_documentos')
                    ->nullOnDelete();
            }
        });

        // ===== (OPCIONAL) KARDEX: trazabilidad del concepto =====
        if (Schema::hasTable('kardex_movimientos')) {
            Schema::table('kardex_movimientos', function (Blueprint $table) {
                if (!Schema::hasColumn('kardex_movimientos', 'concepto_documento_id')) {
                    $table->foreignId('concepto_documento_id')
                        ->nullable()
                        ->after('tipo_documento_id')
                        ->constrained('conceptos_documentos')
                        ->nullOnDelete();
                }
                // Si quisieras guardar el rol usado (inventario, gasto, etc.)
                if (!Schema::hasColumn('kardex_movimientos', 'concepto_rol')) {
                    $table->string('concepto_rol', 40)->nullable()->after('concepto_documento_id');
                }
            });
        }
    }

    public function down(): void
    {
        // Revertir ENTRADAS MERCANCIA
        Schema::table('entradas_mercancia', function (Blueprint $table) {
            if (Schema::hasColumn('entradas_mercancia', 'concepto_documento_id')) {
                $table->dropConstrainedForeignId('concepto_documento_id');
            }
        });

        // Revertir (opcional) KARDEX
        if (Schema::hasTable('kardex_movimientos')) {
            Schema::table('kardex_movimientos', function (Blueprint $table) {
                if (Schema::hasColumn('kardex_movimientos', 'concepto_documento_id')) {
                    $table->dropConstrainedForeignId('concepto_documento_id');
                }
                if (Schema::hasColumn('kardex_movimientos', 'concepto_rol')) {
                    $table->dropColumn('concepto_rol');
                }
            });
        }
    }
};
