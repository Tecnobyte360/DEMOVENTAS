<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('socio_negocio_cuentas')) return;

        $driver = DB::getDriverName();

        Schema::table('socio_negocio_cuentas', function (Blueprint $table) {
            // Asegura que no existan FKs previas (nombres pueden variar)
            foreach ([
                'cuenta_cxc_id', 'cuenta_anticipos_id', 'cuenta_descuentos_id',
                'cuenta_ret_fuente_id', 'cuenta_ret_ica_id', 'cuenta_iva_id',
            ] as $col) {
                // quita FK si existe (silencioso entre motores)
                try { $table->dropForeign([''.$col.'']); } catch (\Throwable $e) {}
            }
        });

        // Vuelve a crear con estrategia por motor
        Schema::table('socio_negocio_cuentas', function (Blueprint $table) use ($driver) {
            $map = [
                'cuenta_cxc_id'        => 'fk_snc_cxc',
                'cuenta_anticipos_id'  => 'fk_snc_anticipos',
                'cuenta_descuentos_id' => 'fk_snc_desc',
                'cuenta_ret_fuente_id' => 'fk_snc_ret_fuente',
                'cuenta_ret_ica_id'    => 'fk_snc_ret_ica',
                'cuenta_iva_id'        => 'fk_snc_iva',
            ];

            foreach ($map as $col => $name) {
                $fk = $table->foreign($col, $name)->references('id')->on('plan_cuentas');
                if ($driver === 'sqlsrv' || $driver === 'pgsql' || $driver === 'sqlite') {
                    // Evita cascadas en SQL Server (y mantenemos coherencia en otros)
                    $fk->restrictOnDelete()->cascadeOnUpdate();
                } else {
                    // En MySQL podrías permitir SET NULL si lo deseas:
                    // $fk->nullOnDelete()->cascadeOnUpdate();
                    $fk->restrictOnDelete()->cascadeOnUpdate();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('socio_negocio_cuentas', function (Blueprint $table) {
            foreach ([
                'fk_snc_cxc','fk_snc_anticipos','fk_snc_desc',
                'fk_snc_ret_fuente','fk_snc_ret_ica','fk_snc_iva',
            ] as $name) {
                try { $table->dropForeign($name); } catch (\Throwable $e) {}
            }
        });
    }
};
