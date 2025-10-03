<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
       public function up(): void
    {
        Schema::table('socio_negocios', function (Blueprint $table) {
            $table->foreignId('condicion_pago_id')
                  ->nullable()
                  ->constrained('condicion_pagos');
        });
    }

    public function down(): void
    {
        Schema::table('socio_negocios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('condicion_pago_id');
        });
    }
};
