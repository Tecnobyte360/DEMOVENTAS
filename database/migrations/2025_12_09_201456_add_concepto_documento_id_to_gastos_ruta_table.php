<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('gastos_ruta', function (Blueprint $table) {
            $table->unsignedBigInteger('concepto_documento_id')
                  ->nullable()
                  ->after('tipo_gasto_id');

            $table->foreign('concepto_documento_id')
                  ->references('id')
                  ->on('conceptos_documentos');
        });
    }

    public function down(): void
    {
        Schema::table('gastos_ruta', function (Blueprint $table) {
            $table->dropForeign(['concepto_documento_id']);
            $table->dropColumn('concepto_documento_id');
        });
    }
};
