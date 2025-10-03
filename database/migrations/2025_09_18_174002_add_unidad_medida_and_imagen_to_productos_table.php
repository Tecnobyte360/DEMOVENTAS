<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // FK a unidades_medida (nullable para no romper datos existentes)
            $table->foreignId('unidad_medida_id')
                  ->nullable()
                  ->constrained('unidades_medida')
                  ->nullOnDelete(); 

            // Ruta (storage/app/public/...) a la imagen del producto
            $table->string('imagen_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // Elimina FK y columna
            $table->dropConstrainedForeignId('unidad_medida_id');
            $table->dropColumn('imagen_path');
        });
    }
};
