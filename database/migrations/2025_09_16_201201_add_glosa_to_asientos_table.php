<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('asientos', function (Blueprint $table) {
            $table->text('glosa')->nullable()->after('columna_existente'); 
            // cambia "columna_existente" por la columna después de la cual quieres que aparezca
        });
    }

    public function down(): void
    {
        Schema::table('asientos', function (Blueprint $table) {
            $table->dropColumn('glosa');
        });
    }
};
