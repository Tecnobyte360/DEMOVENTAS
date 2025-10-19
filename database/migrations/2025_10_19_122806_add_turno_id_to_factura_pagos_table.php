<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('factura_pagos', function (Blueprint $table) {
            $table->foreignId('turno_id')
                  ->nullable()
                  ->constrained('turnos_caja')
                  ->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::table('factura_pagos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('turno_id');
        });
    }
};
