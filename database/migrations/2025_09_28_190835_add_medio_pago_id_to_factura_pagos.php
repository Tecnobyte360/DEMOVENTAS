<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('factura_pagos', function (Blueprint $table) {
            $table->unsignedBigInteger('medio_pago_id')->nullable()->after('fecha');
        });
    }

    public function down(): void
    {
        Schema::table('factura_pagos', function (Blueprint $table) {
            $table->dropColumn('medio_pago_id');
        });
    }
};
