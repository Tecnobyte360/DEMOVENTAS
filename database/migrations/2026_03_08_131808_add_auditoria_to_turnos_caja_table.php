<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('turnos_caja', function (Blueprint $table) {
            $table->foreignId('abierto_por_id')->nullable()->after('user_id');
            $table->foreignId('cerrado_por_id')->nullable()->after('abierto_por_id');
        });

        $driver = DB::getDriverName();

        Schema::table('turnos_caja', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql') {
                $table->foreign('abierto_por_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('cerrado_por_id')->references('id')->on('users')->nullOnDelete();
            } else {
                $table->foreign('abierto_por_id')->references('id')->on('users');
                $table->foreign('cerrado_por_id')->references('id')->on('users');
            }
        });
    }

    public function down(): void
    {
        Schema::table('turnos_caja', function (Blueprint $table) {
            $table->dropForeign(['abierto_por_id']);
            $table->dropForeign(['cerrado_por_id']);
            $table->dropColumn([
                'abierto_por_id',
                'cerrado_por_id',
            ]);
        });
    }
};