<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Asegura que existe la tabla
        if (!Schema::hasTable('movimientos')) {
            // si quieres, puedes lanzar una excepción:
            // throw new \RuntimeException("La tabla 'movimientos' no existe.");
            return;
        }

        // 1) Crear columnas si no existen
        Schema::table('movimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('movimientos', 'detalle')) {
                // equivalente a NVARCHAR(255) -> VARCHAR(255)
                $table->string('detalle', 255)->nullable();
            }
            if (!Schema::hasColumn('movimientos', 'debe')) {
                $table->decimal('debe', 18, 2)->default(0)->nullable(false);
            }
            if (!Schema::hasColumn('movimientos', 'haber')) {
                $table->decimal('haber', 18, 2)->default(0)->nullable(false);
            }
        });

        // 2) Si ya existen, ajustar el tipo (sin instalar doctrine/dbal)
        if (Schema::hasColumn('movimientos', 'detalle')) {
            DB::statement("ALTER TABLE movimientos MODIFY COLUMN detalle VARCHAR(255) NULL");
        }
        if (Schema::hasColumn('movimientos', 'debe')) {
            DB::statement("ALTER TABLE movimientos MODIFY COLUMN debe DECIMAL(18,2) NOT NULL DEFAULT 0");
        }
        if (Schema::hasColumn('movimientos', 'haber')) {
            DB::statement("ALTER TABLE movimientos MODIFY COLUMN haber DECIMAL(18,2) NOT NULL DEFAULT 0");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('movimientos')) {
            return;
        }

        // Reversa "suave": deja las columnas permisivas (sin NOT NULL / DEFAULT)
        // Si prefieres borrarlas, cambia por dropColumn().
        if (Schema::hasColumn('movimientos', 'detalle')) {
            DB::statement("ALTER TABLE movimientos MODIFY COLUMN detalle VARCHAR(255) NULL");
            // o: Schema::table('movimientos', fn (Blueprint $t) => $t->dropColumn('detalle'));
        }
        if (Schema::hasColumn('movimientos', 'debe')) {
            DB::statement("ALTER TABLE movimientos MODIFY COLUMN debe DECIMAL(18,2) NULL DEFAULT NULL");
        }
        if (Schema::hasColumn('movimientos', 'haber')) {
            DB::statement("ALTER TABLE movimientos MODIFY COLUMN haber DECIMAL(18,2) NULL DEFAULT NULL");
        }
    }
};
