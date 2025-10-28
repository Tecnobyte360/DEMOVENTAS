<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factura_pagos', function (Blueprint $table) {
            $table->string('estado', 20)->default('registrado')->after('turno_id');
        });
    }

    public function down(): void
    {
        Schema::table('factura_pagos', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
