<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('socio_negocios', function (Blueprint $table) {
            $table->unsignedBigInteger('cuenta_cxc_id')
                ->nullable()
                ->after('email'); 

            $table->foreign('cuenta_cxc_id')
                ->references('id')->on('plan_cuentas');
            $table->index('cuenta_cxc_id');
        });
    }

    public function down(): void
    {
        Schema::table('socio_negocios', function (Blueprint $table) {
            $table->dropForeign(['cuenta_cxc_id']);
            $table->dropIndex(['cuenta_cxc_id']);
            $table->dropColumn('cuenta_cxc_id');
        });
    }
};
