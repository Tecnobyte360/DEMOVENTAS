<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conductor_ruta', function (Blueprint $table) {
            $table->boolean('aprobada')->default(false)->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('conductor_ruta', function (Blueprint $table) {
            $table->dropColumn('aprobada');
        });
    }
};
