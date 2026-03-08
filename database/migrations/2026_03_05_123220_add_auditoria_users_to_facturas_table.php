<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->foreignId('creado_por_id')->nullable()->after('empresa_id');
            $table->foreignId('actualizado_por_id')->nullable()->after('creado_por_id');
            $table->foreignId('anulado_por_id')->nullable()->after('actualizado_por_id');
            $table->timestamp('anulado_en')->nullable()->after('anulado_por_id');

            $table->foreignId('emitido_por_id')->nullable()->after('anulado_en');
            $table->timestamp('emitido_en')->nullable()->after('emitido_por_id');
        });

        // ✅ FKs diferentes según motor
        $driver = DB::getDriverName(); // mysql | sqlsrv | pgsql | sqlite...

        Schema::table('facturas', function (Blueprint $table) use ($driver) {

            if ($driver === 'mysql') {
                // MySQL: OK usar SET NULL
                $table->foreign('creado_por_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('actualizado_por_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('anulado_por_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('emitido_por_id')->references('id')->on('users')->nullOnDelete();
            } else {
                // SQL Server (y otros): NO ACTION (default) para evitar multiple cascade paths
                $table->foreign('creado_por_id')->references('id')->on('users');
                $table->foreign('actualizado_por_id')->references('id')->on('users');
                $table->foreign('anulado_por_id')->references('id')->on('users');
                $table->foreign('emitido_por_id')->references('id')->on('users');
            }
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            // dropear FKs primero
            $table->dropForeign(['creado_por_id']);
            $table->dropForeign(['actualizado_por_id']);
            $table->dropForeign(['anulado_por_id']);
            $table->dropForeign(['emitido_por_id']);

            // luego columnas
            $table->dropColumn([
                'creado_por_id',
                'actualizado_por_id',
                'anulado_por_id',
                'anulado_en',
                'emitido_por_id',
                'emitido_en',
            ]);
        });
    }
};