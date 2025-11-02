<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EntradasMercanciaTipoYSerieSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Tipo de documento
        $tipoId = DB::table('tipo_documentos')->whereRaw('LOWER(codigo)=LOWER(?)', ['ENTRADA_MERCANCIA'])->value('id');
        if (!$tipoId) {
            $tipoId = DB::table('tipo_documentos')->insertGetId([
                'codigo' => 'ENTRADA_MERCANCIA',
                'nombre' => 'Entrada de Mercancía',
                'modulo' => 'inventario',
                'config' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2) Serie default (ajusta rango y prefijo a tu gusto)
        $serie = DB::table('series')->where('tipo_documento_id', $tipoId)->where('es_default', 1)->first();
        if (!$serie) {
            DB::table('series')->insert([
                'nombre'            => 'Entrada Default',
                'prefijo'           => 'EM',
                'tipo_documento_id' => $tipoId,
                'es_default'        => 1,
                'desde'             => 1,
                'hasta'             => 999999,
                'proximo'           => 1,
                'longitud'          => 6,
                'resolucion'        => null,
                'resolucion_fecha'  => null,
                'vigente_desde'     => null,
                'vigente_hasta'     => null,
                'activa'            => 1,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }
}
