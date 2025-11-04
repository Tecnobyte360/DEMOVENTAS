<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up(): void
{
    Schema::table('facturas', function (Blueprint $table) {
        if (!Schema::hasColumn('facturas', 'empresa_id')) {
            $table->foreignId('empresa_id')
                ->nullable()
                ->constrained('empresas')   
                ->nullOnDelete();
        }
    });
}

public function down(): void
{
    Schema::table('facturas', function (Blueprint $table) {
        if (Schema::hasColumn('facturas', 'empresa_id')) {
            $table->dropConstrainedForeignId('empresa_id');
        }
    });
}

};
