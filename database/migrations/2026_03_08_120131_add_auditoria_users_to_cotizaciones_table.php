<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->foreignId('creado_por_id')->nullable()->after('id');
            $table->foreignId('actualizado_por_id')->nullable()->after('creado_por_id');
            $table->foreignId('anulado_por_id')->nullable()->after('actualizado_por_id');
            $table->timestamp('anulado_en')->nullable()->after('anulado_por_id');

            $table->foreignId('emitido_por_id')->nullable()->after('anulado_en');
            $table->timestamp('emitido_en')->nullable()->after('emitido_por_id');
        });

        $driver = DB::getDriverName();

        Schema::table('cotizaciones', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->foreign('creado_por_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('actualizado_por_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('anulado_por_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('emitido_por_id')->references('id')->on('users')->nullOnDelete();
            } else {
                $table->foreign('creado_por_id')->references('id')->on('users');
                $table->foreign('actualizado_por_id')->references('id')->on('users');
                $table->foreign('anulado_por_id')->references('id')->on('users');
                $table->foreign('emitido_por_id')->references('id')->on('users');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropForeign(['creado_por_id']);
            $table->dropForeign(['actualizado_por_id']);
            $table->dropForeign(['anulado_por_id']);
            $table->dropForeign(['emitido_por_id']);

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