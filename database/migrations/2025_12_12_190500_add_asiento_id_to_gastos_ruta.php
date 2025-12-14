<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('gastos_ruta', function (Blueprint $table) {

            // Si existe caja_movimiento_id, la pone después. Si no, la crea sin after.
            if (Schema::hasColumn('gastos_ruta', 'caja_movimiento_id')) {
                $table->unsignedBigInteger('asiento_id')->nullable()->after('caja_movimiento_id');
            } else {
                $table->unsignedBigInteger('asiento_id')->nullable();
            }

            // FK (solo si no existe ya)
            $table->foreign('asiento_id')
                ->references('id')
                ->on('asientos')
                ->nullOnDelete(); // equivalente a onDelete('set null')
        });
    }

    public function down()
    {
        Schema::table('gastos_ruta', function (Blueprint $table) {
            $table->dropForeign(['asiento_id']);
            $table->dropColumn('asiento_id');
        });
    }
};
