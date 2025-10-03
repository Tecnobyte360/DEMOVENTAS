<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_subcategoria_cuentas_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
       Schema::create('subcategoria_cuentas', function (Blueprint $table) {
    $table->id();

    // Subcategoría: si se elimina, cae en cascada
    $table->foreignId('subcategoria_id')
          ->constrained('subcategorias')
          ->cascadeOnDelete();

    // Tipo: NO usar restrictOnDelete (default = NO ACTION en SQL Server)
    $table->foreignId('tipo_id')
          ->constrained('producto_cuenta_tipos'); // sin onDelete

    // PUC: NO usar restrictOnDelete
    $table->foreignId('plan_cuentas_id')
          ->constrained('plan_cuentas'); // sin onDelete

    $table->unique(['subcategoria_id','tipo_id']);
    $table->timestamps();
});

    }

    public function down(): void
    {
        Schema::dropIfExists('subcategoria_cuentas');
    }
};
