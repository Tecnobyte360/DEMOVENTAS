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
        Schema::table('pagos', function (Blueprint $table) {
            // Agrega la columna user_id después de socio_negocio_id (puedes ajustar la posición si deseas)
            $table->foreignId('user_id')->nullable()->constrained('users')->after('socio_negocio_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            // Elimina la relación y luego la columna
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
