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
             $table->string('nombre');
            $table->string('nit')->nullable();
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->string('sitio_web')->nullable();
            $table->string('direccion')->nullable();
            $table->string('logo_path')->nullable();      
            $table->string('logo_dark_path')->nullable(); 
            $table->string('favicon_path')->nullable();
            $table->string('color_primario')->nullable();  
            $table->string('color_secundario')->nullable(); 
            $table->boolean('is_activa')->default(true);

            $table->json('extra')->nullable(); 
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
