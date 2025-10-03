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
    Schema::table('productos', function (Blueprint $table) {
        if (!Schema::hasColumn('productos', 'costo')) {
            $table->decimal('costo', 10, 2)->default(0);
        }
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // Elimina el campo costo si se hace rollback
            $table->dropColumn('costo');
        });
    }
};
