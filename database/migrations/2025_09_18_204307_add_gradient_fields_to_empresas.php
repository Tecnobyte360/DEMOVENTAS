<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('empresas', function (Blueprint $table) {
            $table->boolean('usar_gradiente')->default(false);
            $table->unsignedSmallInteger('grad_angle')->default(135); 
        });
    }
    public function down(): void {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['usar_gradiente','grad_angle']);
        });
    }
};
