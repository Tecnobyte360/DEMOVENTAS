<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('socio_negocio_cuentas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('socio_negocio_id')->unique();

            $table->foreignId('cuenta_cxc_id')->nullable();
            $table->foreignId('cuenta_anticipos_id')->nullable();
            $table->foreignId('cuenta_descuentos_id')->nullable();
            $table->foreignId('cuenta_ret_fuente_id')->nullable();
            $table->foreignId('cuenta_ret_ica_id')->nullable();
            $table->foreignId('cuenta_iva_id')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socio_negocio_cuentas');
    }
};
