<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transferencias_stock', function (Blueprint $table) {
    
            $table->index(
                ['producto_id', 'bodega_origen_id', 'bodega_destino_id'],
                'idx_ts_prod_bod_ori_des'
            );
        });
    }

    public function down(): void
    {
        Schema::table('transferencias_stock', function (Blueprint $table) {
            $table->dropIndex('idx_ts_prod_bod_ori_des');
        });
    }
};
