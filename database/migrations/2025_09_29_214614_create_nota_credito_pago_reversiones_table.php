<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function fkExists(string $table, string $constraint): bool
    {
        return DB::selectOne(
            "SELECT 1
               FROM information_schema.TABLE_CONSTRAINTS
              WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND CONSTRAINT_NAME = ?
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
              LIMIT 1",
            [$table, $constraint]
        ) !== null;
    }

    public function up(): void
    {
        // 1) Crea la tabla si no existe
        if (!Schema::hasTable('nota_credito_pago_reversiones')) {
            Schema::create('nota_credito_pago_reversiones', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('nota_credito_id')->nullable();
                $table->unsignedBigInteger('pago_factura_id')->nullable();
                $table->decimal('monto_revertido', 18, 2)->default(0);
                $table->timestamps();
            });
        }

        // 2) Asegura columnas si faltan (por si la tabla ya existía)
        Schema::table('nota_credito_pago_reversiones', function (Blueprint $table) {
            if (!Schema::hasColumn('nota_credito_pago_reversiones', 'nota_credito_id')) {
                $table->unsignedBigInteger('nota_credito_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('nota_credito_pago_reversiones', 'pago_factura_id')) {
                $table->unsignedBigInteger('pago_factura_id')->nullable()->after('nota_credito_id');
            }
            if (!Schema::hasColumn('nota_credito_pago_reversiones', 'monto_revertido')) {
                $table->decimal('monto_revertido', 18, 2)->default(0)->after('pago_factura_id');
            }
        });

        // 3) Alinea tipos si hiciera falta (opcional; sin doctrine/dbal)
        // DB::statement("ALTER TABLE nota_credito_pago_reversiones
        //                MODIFY COLUMN pago_factura_id BIGINT UNSIGNED NULL");

        // 4) Agrega FK solo si:
        //    - existen tablas padre/hija
        //    - no existe ya la constraint
        if (Schema::hasTable('pagos_facturas') &&
            Schema::hasTable('nota_credito_pago_reversiones') &&
            !$this->fkExists('nota_credito_pago_reversiones', 'ncp_rev_pago_factura_id_fk')) {

            Schema::table('nota_credito_pago_reversiones', function (Blueprint $table) {
                $table->foreign('pago_factura_id', 'ncp_rev_pago_factura_id_fk')
                      ->references('id')->on('pagos_facturas')
                      ->cascadeOnDelete();
            });
        }

        // (Si también necesitas FK a notas_credito, agrega otro bloque similar)
    }

    public function down(): void
    {
        // Elimina FK si existe y luego la tabla
        Schema::table('nota_credito_pago_reversiones', function (Blueprint $table) {
            try { $table->dropForeign('ncp_rev_pago_factura_id_fk'); } catch (\Throwable $e) {}
            try { $table->dropForeign(['pago_factura_id']); } catch (\Throwable $e) {}
        });

        Schema::dropIfExists('nota_credito_pago_reversiones');
    }
};
