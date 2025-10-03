<?php
// database/seeders/UnidadMedidaSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnidadMedidaSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['codigo'=>'UND','nombre'=>'Unidad','simbolo'=>'u','tipo'=>'otro','activo'=>true],
            ['codigo'=>'KG','nombre'=>'Kilogramo','simbolo'=>'kg','tipo'=>'masa','activo'=>true],
            ['codigo'=>'G','nombre'=>'Gramo','simbolo'=>'g','tipo'=>'masa','activo'=>true],
            ['codigo'=>'LT','nombre'=>'Litro','simbolo'=>'L','tipo'=>'volumen','activo'=>true],
            ['codigo'=>'ML','nombre'=>'Mililitro','simbolo'=>'mL','tipo'=>'volumen','activo'=>true],
            ['codigo'=>'M','nombre'=>'Metro','simbolo'=>'m','tipo'=>'longitud','activo'=>true],
            ['codigo'=>'CM','nombre'=>'Centímetro','simbolo'=>'cm','tipo'=>'longitud','activo'=>true],
            ['codigo'=>'PKG','nombre'=>'Paquete','simbolo'=>null,'tipo'=>'otro','activo'=>true],
            ['codigo'=>'H','nombre'=>'Hora','simbolo'=>'h','tipo'=>'tiempo','activo'=>true],
            ['codigo'=>'SERV','nombre'=>'Servicio','simbolo'=>null,'tipo'=>'otro','activo'=>true],
            ['codigo'=>'PAR','nombre'=>'Par','simbolo'=>null,'tipo'=>'otro','activo'=>true],
        ];

        foreach ($rows as $r) {
            DB::table('unidades_medida')->updateOrInsert(
                ['codigo' => $r['codigo']],
                $r
            );
        }
    }
}
