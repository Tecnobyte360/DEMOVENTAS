<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('salidas_manuales_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salida_manual_id')->constrained('salidas_manuales')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained()->onDelete('cascade');
            $table->foreignId('bodega_id')->constrained()->onDelete('cascade');
            $table->decimal('cantidad', 12, 3);
            $table->string('observacion')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('salidas_manuales_detalles'); }
};
