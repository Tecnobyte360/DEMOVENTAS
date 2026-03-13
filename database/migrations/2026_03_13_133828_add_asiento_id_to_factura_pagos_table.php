<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('factura_pagos', function (Blueprint $table) {
            if (!Schema::hasColumn('factura_pagos', 'asiento_id')) {
                $table->unsignedBigInteger('asiento_id')->nullable()->after('user_id');

                $table->foreign('asiento_id')
                    ->references('id')
                    ->on('asientos')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('factura_pagos', function (Blueprint $table) {
            if (Schema::hasColumn('factura_pagos', 'asiento_id')) {
                $table->dropForeign(['asiento_id']);
                $table->dropColumn('asiento_id');
            }
        });
    }
};