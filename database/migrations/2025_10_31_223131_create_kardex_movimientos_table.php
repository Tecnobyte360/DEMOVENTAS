<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
      Schema::create('kardex_movimientos', function (Blueprint $table) {
    $table->id();

    $table->dateTime('fecha')->index();

    $table->foreignId('producto_id')
        ->constrained('productos')
        ->cascadeOnDelete();

    $table->foreignId('bodega_id')
        ->constrained('bodegas')
        ->cascadeOnDelete();

    // Nueva relación
    $table->foreignId('tipo_documento_id')
        ->nullable()
        ->constrained('tipo_documentos')
        ->nullOnDelete();

    $table->decimal('entrada', 18, 6)->default(0);
    $table->decimal('salida', 18, 6)->default(0);
    $table->decimal('cantidad', 18, 6)->nullable();

    $table->smallInteger('signo')->nullable(); // 1 o -1

    $table->decimal('costo_unitario', 18, 6)->nullable();
    $table->decimal('total', 18, 2)->nullable();

    $table->string('doc_id', 100)->nullable();
    $table->string('ref', 255)->nullable();

    $table->timestamps();

    $table->index(['producto_id', 'bodega_id', 'fecha']);
});

    }

    public function down(): void
    {
        Schema::dropIfExists('kardex_movimientos');
    }
};
