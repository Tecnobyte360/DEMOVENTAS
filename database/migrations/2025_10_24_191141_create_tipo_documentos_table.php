<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tipo_documentos', function (Blueprint $table) {
            $table->id();

            // Identificador lógico del tipo (único y estable en código)
            $table->string('codigo', 40)->unique();   // ej: factura, pedido, nota_credito, facturacompra

            // Nombre visible para usuarios
            $table->string('nombre', 120);            // ej: Factura de venta

            // Clasificación opcional (ventas, compras, inventario, etc.)
            $table->string('modulo', 60)->nullable();

            // Configuración opcional en JSON (reglas, banderas, etc.)
            $table->json('config')->nullable();

            $table->timestamps();
        });

        // Seed mínimo (opcional). Borra esto si prefieres usar seeders separados.
        DB::table('tipo_documentos')->insert([
            ['codigo'=>'factura',       'nombre'=>'Factura de venta',   'modulo'=>'ventas',   'config'=>json_encode(['afecta_stock'=>true])],
            ['codigo'=>'oferta',        'nombre'=>'Oferta de venta',    'modulo'=>'ventas',   'config'=>json_encode(['afecta_stock'=>false])],
            ['codigo'=>'pedido',        'nombre'=>'Pedido de venta',    'modulo'=>'ventas',   'config'=>json_encode(['afecta_stock'=>false])],
            ['codigo'=>'nota_credito',  'nombre'=>'Nota crédito',       'modulo'=>'ventas',   'config'=>json_encode(['afecta_stock'=>false])],
            ['codigo'=>'facturacompra', 'nombre'=>'Factura de compra',  'modulo'=>'compras',  'config'=>json_encode(['afecta_stock'=>true])],
            ['codigo'=>'otro',          'nombre'=>'Otro',               'modulo'=>null,       'config'=>null],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_documentos');
    }
};
