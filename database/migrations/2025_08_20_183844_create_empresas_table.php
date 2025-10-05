<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();

            // Información básica
            $table->string('nombre');
            $table->string('nit')->nullable();
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->string('sitio_web')->nullable();
            $table->string('direccion')->nullable();

            // Logos e íconos (Base64, por eso longText)
            $table->longText('logo_path')->nullable();       // data:image/png;base64,...
            $table->longText('logo_dark_path')->nullable();  // versión oscura
            $table->longText('favicon_path')->nullable();    // favicon o ícono

            // Colores y estado
            $table->string('color_primario')->nullable();
            $table->string('color_secundario')->nullable();
            $table->boolean('is_activa')->default(true);

            // Configuración adicional
            $table->json('extra')->nullable();

            // Opcionales futuros (si ya los tienes, los dejamos comentados)
            // $table->boolean('usar_gradiente')->default(false);
            // $table->smallInteger('grad_angle')->default(135);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
