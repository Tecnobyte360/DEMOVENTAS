<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('entradas_mercancia', function (Blueprint $table) {
            // Relación con series (nullable para no romper históricos)
            $table->foreignId('serie_id')
                ->nullable()
                ->after('socio_negocio_id')
                ->constrained('series')
                ->nullOnDelete();

            // Numeración y estado
            $table->string('prefijo', 20)->nullable()->after('serie_id');
            $table->unsignedBigInteger('numero')->nullable()->after('prefijo');
            $table->string('estado', 20)->default('borrador')->after('observaciones');

            // Índices con nombre explícito
            $table->index(['serie_id', 'numero'], 'em_serie_numero_idx');
            $table->index('estado', 'em_estado_idx');
            $table->index('fecha_contabilizacion', 'em_fecha_idx');
        });
    }

    public function down(): void
    {
        Schema::table('entradas_mercancia', function (Blueprint $table) {
            // Quitar índices
            $table->dropIndex('em_serie_numero_idx');
            $table->dropIndex('em_estado_idx');
            $table->dropIndex('em_fecha_idx');

            // Quitar FK + columnas
            $table->dropConstrainedForeignId('serie_id');
            $table->dropColumn(['prefijo', 'numero', 'estado']);
        });
    }
};
