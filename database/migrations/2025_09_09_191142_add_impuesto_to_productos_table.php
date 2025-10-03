<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->unsignedBigInteger('impuesto_id')->nullable()->after('subcategoria_id');
            $table->foreign('impuesto_id')->references('id')->on('impuestos');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['impuesto_id']);
            $table->dropColumn('impuesto_id');
        });
    }
};
