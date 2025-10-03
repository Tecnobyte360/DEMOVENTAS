<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends \Illuminate\Database\Migrations\Migration {
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->enum('tipo_pago', ['contado', 'credito'])->default('contado')->after('fecha');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn('tipo_pago');
        });
    }
};
