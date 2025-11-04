<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_theme_fields_to_empresas_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            if (!Schema::hasColumn('empresas', 'usar_gradiente')) {
                $table->boolean('usar_gradiente')->default(false)->after('color_secundario');
            }
            if (!Schema::hasColumn('empresas', 'grad_angle')) {
                $table->unsignedSmallInteger('grad_angle')->default(135)->after('usar_gradiente');
            }
            if (!Schema::hasColumn('empresas', 'pdf_theme')) {
                $table->json('pdf_theme')->nullable()->after('extra');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            if (Schema::hasColumn('empresas', 'usar_gradiente')) $table->dropColumn('usar_gradiente');
            if (Schema::hasColumn('empresas', 'grad_angle'))     $table->dropColumn('grad_angle');
            if (Schema::hasColumn('empresas', 'pdf_theme'))      $table->dropColumn('pdf_theme');
        });
    }
};
