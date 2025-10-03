<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('asientos', function (Blueprint $table) {
            // decimal grande típico en contabilidad
            $table->decimal('debe', 18, 2)->default(0)->after('glosa'); // ajusta el after según tu orden
        });
    }

    public function down(): void
    {
        Schema::table('asientos', function (Blueprint $table) {
            $table->dropColumn('debe');
        });
    }
};
