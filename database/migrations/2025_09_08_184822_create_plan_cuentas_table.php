<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plan_cuentas', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Estructura
            $table->string('codigo', 30)->unique();
            $table->string('nombre');
            $table->unsignedTinyInteger('nivel')->default(1);
            $table->unsignedBigInteger('padre_id')->nullable(); // FK se agrega en otra migración

            // Naturaleza / estado
            $table->string('naturaleza', 40)->default('ACTIVOS');
            $table->boolean('cuenta_activa')->default(true);
            $table->boolean('titulo')->default(false);

            // Metadatos
            $table->string('moneda', 20)->default('Pesos Colombianos');
            $table->boolean('requiere_tercero')->default(false);
            $table->boolean('confidencial')->default(false);
            $table->unsignedTinyInteger('nivel_confidencial')->nullable();

            // SAP-like
            $table->string('clase_cuenta', 40)->nullable();
            $table->boolean('cuenta_monetaria')->default(false);
            $table->boolean('cuenta_asociada')->default(false);
            $table->boolean('revalua_indice')->default(false);
            $table->boolean('bloquear_contab_manual')->default(false);

            // Relevancias
            $table->boolean('relevante_flujo_caja')->default(false);
            $table->boolean('relevante_costos')->default(false);

            // Dimensiones
            $table->string('dimension1')->nullable();
            $table->string('dimension2')->nullable();
            $table->string('dimension3')->nullable();
            $table->string('dimension4')->nullable();

            // Saldo
            $table->decimal('saldo', 18, 2)->default(0);

            $table->timestamps();

            $table->index(['padre_id', 'nivel']);
            $table->index('naturaleza');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_cuentas');
    }
};
