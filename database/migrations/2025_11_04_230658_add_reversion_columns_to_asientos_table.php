<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('asientos', function (Blueprint $table) {
            if (!Schema::hasColumn('asientos', 'reverso_de_asiento_id')) {
                $table->unsignedBigInteger('reverso_de_asiento_id')->nullable()->index();
            }
            if (!Schema::hasColumn('asientos', 'revertido_por_asiento_id')) {
                $table->unsignedBigInteger('revertido_por_asiento_id')->nullable()->index();
            }
            if (!Schema::hasColumn('asientos', 'estado')) {
                $table->string('estado')->default('emitido')->index(); // emitido|revertido|anulado
            }
        });
    }

    public function down(): void
    {
        Schema::table('asientos', function (Blueprint $table) {
            $table->dropColumn(['reverso_de_asiento_id', 'revertido_por_asiento_id', 'estado']);
        });
    }
};
