<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function columnExists(string $table, string $column): bool
    {
        $row = DB::selectOne("
            SELECT COUNT(*) AS c
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ", [$table, $column]);

        return ((int)($row->c ?? 0)) > 0;
    }

    public function up(): void
    {
        // ✅ Agregar unidad_medida_id SOLO si NO existe (check real en MySQL)
        if (!$this->columnExists('productos', 'unidad_medida_id')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->foreignId('unidad_medida_id')
                    ->nullable()
                    ->constrained('unidades_medida')
                    ->nullOnDelete();
            });
        }

        // ✅ Manejar imagen_path según exista o no (check real en MySQL)
        if ($this->columnExists('productos', 'imagen_path')) {
            // Si ya existe, la convertimos a longText nullable
            Schema::table('productos', function (Blueprint $table) {
                $table->longText('imagen_path')->nullable()->change();
            });
        } else {
            // Si no existe, la creamos
            Schema::table('productos', function (Blueprint $table) {
                $table->longText('imagen_path')
                    ->nullable()
                    ->after('unidad_medida_id');
            });
        }
    }

    public function down(): void
    {
        // ✅ Eliminar FK y columna solo si existen (check real en MySQL)
        if ($this->columnExists('productos', 'unidad_medida_id')) {
            Schema::table('productos', function (Blueprint $table) {
                // Si ya existe la FK, esto la elimina junto con la columna en Laravel
                $table->dropConstrainedForeignId('unidad_medida_id');
            });
        }

        if ($this->columnExists('productos', 'imagen_path')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropColumn('imagen_path');
            });
        }
    }
};
