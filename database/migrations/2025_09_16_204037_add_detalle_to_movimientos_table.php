<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('movimientos', 'detalle')) {
                $table->text('detalle')->nullable(); // equivalente a LONGTEXT en MySQL
            }
            if (!Schema::hasColumn('movimientos', 'debe')) {
                $table->decimal('debe', 18, 2)->default(0);
            }
            if (!Schema::hasColumn('movimientos', 'haber')) {
                $table->decimal('haber', 18, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            if (Schema::hasColumn('movimientos', 'detalle')) {
                $table->dropColumn('detalle');
            }
            if (Schema::hasColumn('movimientos', 'debe')) {
                $table->dropColumn('debe');
            }
            if (Schema::hasColumn('movimientos', 'haber')) {
                $table->dropColumn('haber');
            }
        });
    }
};
