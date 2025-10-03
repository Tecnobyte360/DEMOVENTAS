<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('asientos', function (Blueprint $table) {
            // Evita errores si ya existen (útil en SQL Server)
            if (!Schema::hasColumn('asientos', 'origen')) {
                $table->string('origen', 50)->nullable()->after('glosa'); // ej: 'factura'
            }
            if (!Schema::hasColumn('asientos', 'origen_id')) {
                $table->unsignedBigInteger('origen_id')->nullable()->after('origen'); // id del documento origen
                $table->index('origen_id');
                $table->index(['origen', 'origen_id']);
            }

            // (Opcional) por si aún no los tienes
            if (!Schema::hasColumn('asientos', 'moneda')) {
                $table->string('moneda', 3)->default('COP')->after('origen_id');
            }
            if (!Schema::hasColumn('asientos', 'total_debe')) {
                $table->decimal('total_debe', 18, 2)->default(0)->after('moneda');
            }
            if (!Schema::hasColumn('asientos', 'total_haber')) {
                $table->decimal('total_haber', 18, 2)->default(0)->after('total_debe');
            }
        });
    }

    public function down(): void
    {
        Schema::table('asientos', function (Blueprint $table) {
            if (Schema::hasColumn('asientos', 'origen_id')) {
                $table->dropIndex(['origen_id']);
                $table->dropIndex(['asientos_origen_origen_id_index']); // ignora si no existe
                $table->dropColumn('origen_id');
            }
            if (Schema::hasColumn('asientos', 'origen')) {
                $table->dropColumn('origen');
            }
            // No toco moneda/total_debe/total_haber para no romper otros datos
        });
    }
};
