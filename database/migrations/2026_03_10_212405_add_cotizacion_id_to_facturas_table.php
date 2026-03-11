<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('facturas', 'cotizacion_id')) {
            Schema::table('facturas', function (Blueprint $table) {
                $table->unsignedBigInteger('cotizacion_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('facturas', 'cotizacion_id')) {
            Schema::table('facturas', function (Blueprint $table) {
                $table->dropColumn('cotizacion_id');
            });
        }
    }
};