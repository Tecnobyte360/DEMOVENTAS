<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            if (!Schema::hasColumn('empresas', 'whatsapp')) {
                $table->string('whatsapp', 50)->nullable()->after('telefono');
            }
            if (!Schema::hasColumn('empresas', 'celular')) {
                $table->string('celular', 50)->nullable()->after('whatsapp');
            }
            if (!Schema::hasColumn('empresas', 'banco_nombre')) {
                $table->string('banco_nombre', 100)->nullable();
            }
            if (!Schema::hasColumn('empresas', 'banco_tipo_cuenta')) {
                $table->string('banco_tipo_cuenta', 50)->nullable();
            }
            if (!Schema::hasColumn('empresas', 'banco_numero_cuenta')) {
                $table->string('banco_numero_cuenta', 50)->nullable();
            }
            if (!Schema::hasColumn('empresas', 'banco_titular')) {
                $table->string('banco_titular', 150)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            foreach (['whatsapp', 'celular', 'banco_nombre', 'banco_tipo_cuenta', 'banco_numero_cuenta', 'banco_titular'] as $col) {
                if (Schema::hasColumn('empresas', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
