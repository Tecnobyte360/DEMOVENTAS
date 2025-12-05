<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 👉 Agregar unidad_medida_id solo si no existe
        if (!Schema::hasColumn('productos', 'unidad_medida_id')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->foreignId('unidad_medida_id')
                    ->nullable()
                    ->constrained('unidades_medida')
                    ->nullOnDelete();
            });
        }

        // 👉 Manejar imagen_path según exista o no
        if (Schema::hasColumn('productos', 'imagen_path')) {
            // Si ya existe, la convertimos a longText nullable
            Schema::table('productos', function (Blueprint $table) {
                $table->longText('imagen_path')->nullable()->change();
            });
        } else {
            // Si no existe, la creamos
            Schema::table('productos', function (Blueprint $table) {
                $table->longText('imagen_path')
                    ->nullable()
                    ->after('unidad_medida_id'); // ajusta posición si quieres
            });
        }
    }

    public function down(): void
    {
        // 👉 Eliminar FK y columna solo si existen
        if (Schema::hasColumn('productos', 'unidad_medida_id')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropConstrainedForeignId('unidad_medida_id');
            });
        }

        if (Schema::hasColumn('productos', 'imagen_path')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropColumn('imagen_path');
            });
        }
    }
};
