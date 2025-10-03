<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asiento_id')
                ->constrained('asientos')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('cuenta_id');     
            $table->unsignedBigInteger('tercero_id')->nullable(); 
            $table->string('descripcion')->nullable();

            $table->decimal('debito', 14, 2)->default(0);
            $table->decimal('credito', 14, 2)->default(0);

            $table->timestamps();

            $table->foreign('cuenta_id')->references('id')->on('plan_cuentas');
            $table->index(['cuenta_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
