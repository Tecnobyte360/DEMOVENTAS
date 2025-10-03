<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('mov_contable_segun', 20)
                  ->default('Subcategoria')
                  ->after('impuesto_id'); 
        });
    }

    public function down(): void {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('mov_contable_segun');
        });
    }
};
