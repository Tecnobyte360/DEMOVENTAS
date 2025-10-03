<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('socio_negocios', function (Blueprint $table) {
      $table->char('tipo_persona',1)->nullable()->comment('N=Natural, J=Jurídica');
      $table->string('regimen_iva',30)->nullable()->comment('no_responsable|responsable');
      $table->boolean('regimen_simple')->nullable();
      $table->unsignedBigInteger('municipio_id')->nullable();         
      $table->string('actividad_economica',10)->nullable();           
      $table->string('direccion_medios_magneticos',255)->nullable();

      $table->index(['tipo_persona']);
      $table->index(['regimen_iva']);
      $table->foreign('municipio_id')->references('id')->on('municipios');
    });
  }
  public function down(): void {
    Schema::table('socio_negocios', function (Blueprint $table) {
      $table->dropForeign(['municipio_id']);
      $table->dropIndex(['tipo_persona']);
      $table->dropIndex(['regimen_iva']);
      $table->dropColumn([
        'tipo_persona','regimen_iva','regimen_simple','municipio_id',
        'actividad_economica','direccion_medios_magneticos'
      ]);
    });
  }
};
