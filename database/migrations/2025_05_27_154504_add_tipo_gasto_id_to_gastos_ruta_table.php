<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('gastos_ruta', function (Blueprint $table) {
            $table->foreignId('tipo_gasto_id')->nullable()->constrained('tipos_gasto')->nullOnDelete();
            $table->dropColumn('tipo'); // eliminar el campo anterior si ya no lo usarás
        });
    }

    public function down(): void
    {
        Schema::table('gastos_ruta', function (Blueprint $table) {
            $table->dropForeign(['tipo_gasto_id']);
            $table->dropColumn('tipo_gasto_id');
            $table->string('tipo'); // lo agregas de vuelta por si haces rollback
        });
    }
};
