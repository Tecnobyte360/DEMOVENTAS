<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('municipios', function (Blueprint $table) {
      $table->id();
      $table->string('codigo_dane',10)->unique();
      $table->string('nombre',120);
      $table->string('departamento',120)->nullable();
      $table->timestamps();
    });
  }
  public function down(): void {
    Schema::dropIfExists('municipios');
  }
};
