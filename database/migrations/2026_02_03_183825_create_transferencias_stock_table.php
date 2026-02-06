<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferencias_stock', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('bodega_origen_id');
            $table->unsignedBigInteger('bodega_destino_id');
            $table->decimal('cantidad', 18, 6);

            $table->decimal('costo_unitario', 18, 6)->default(0);
            $table->decimal('costo_total', 18, 2)->default(0);

            $table->text('observacion')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->timestamps();

            
            $table->index(
                ['producto_id', 'bodega_origen_id', 'bodega_destino_id'],
                'idx_ts_prod_bod_ori_des'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencias_stock');
    }
};
