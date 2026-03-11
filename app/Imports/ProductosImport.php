<?php

namespace App\Imports;

use App\Models\Bodega;
use App\Models\Categorias\Subcategoria;
use App\Models\Impuestos\Impuesto;
use App\Models\Productos\Producto;
use App\Models\UnidadesMedida;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductosImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $index => $row) {
                $fila = $index + 2;

                $nombre = trim((string) ($row['nombre'] ?? ''));
                if ($nombre === '') {
                    continue;
                }

                $descripcion = trim((string) ($row['descripcion'] ?? ''));
                $precio      = $this->toFloat($row['precio'] ?? 0);
                $costo       = $this->toFloat($row['costo'] ?? 0);

                $subcategoriaTexto = trim((string) ($row['subcategoria'] ?? ''));
                $unidadTexto       = trim((string) ($row['unidad'] ?? ''));

                $esInventariable = $this->toBoolean($row['es_inventariable'] ?? true);
                $activo          = $this->toBoolean($row['activo'] ?? true);

                $stockMinimo = $this->valorOpcionalNumerico($row['stock_minimo'] ?? null, 0);
                $stockMaximo = $this->valorOpcionalNumerico($row['stock_maximo'] ?? null, null);

                /*
                 |------------------------------------------------------------
                 | BODEGA POR ID
                 | Acepta primero bodega_id, y si no existe, usa bodega
                 |------------------------------------------------------------
                 */
                $bodegaIdRaw = $row['bodega_id'] ?? ($row['bodega'] ?? null);
                $bodegaIdRaw = is_string($bodegaIdRaw) ? trim($bodegaIdRaw) : $bodegaIdRaw;
                $bodegaId    = ($bodegaIdRaw === '' || $bodegaIdRaw === null) ? null : (int) $bodegaIdRaw;

                /*
                 |------------------------------------------------------------
                 | SUBCATEGORÍA POR NOMBRE
                 |------------------------------------------------------------
                 */
                $subcategoria = Subcategoria::whereRaw(
                    'LOWER(nombre) = ?',
                    [mb_strtolower($subcategoriaTexto)]
                )->first();

                if (!$subcategoria) {
                    throw new \Exception("Fila {$fila}: la subcategoría '{$subcategoriaTexto}' no existe.");
                }

                /*
                 |------------------------------------------------------------
                 | UNIDAD POR NOMBRE
                 |------------------------------------------------------------
                 */
                $unidad = null;
                if ($unidadTexto !== '') {
                    $unidad = UnidadesMedida::whereRaw(
                        'LOWER(nombre) = ?',
                        [mb_strtolower($unidadTexto)]
                    )->first();

                    if (!$unidad) {
                        throw new \Exception("Fila {$fila}: la unidad '{$unidadTexto}' no existe.");
                    }
                }

                /*
                 |------------------------------------------------------------
                 | IMPUESTO
                 | Primero por impuesto_codigo, si no viene por impuesto(nombre)
                 |------------------------------------------------------------
                 */
                $impuesto = null;

                $impuestoCodigo = trim((string) ($row['impuesto_codigo'] ?? ''));
                $impuestoNombre = trim((string) ($row['impuesto'] ?? ''));

                if ($impuestoCodigo !== '') {
                    $impuesto = Impuesto::whereRaw(
                        'LOWER(codigo) = ?',
                        [mb_strtolower($impuestoCodigo)]
                    )->first();

                    if (!$impuesto) {
                        throw new \Exception("Fila {$fila}: el impuesto con código '{$impuestoCodigo}' no existe.");
                    }
                } elseif ($impuestoNombre !== '') {
                    $impuesto = Impuesto::whereRaw(
                        'LOWER(nombre) = ?',
                        [mb_strtolower($impuestoNombre)]
                    )->first();

                    if (!$impuesto) {
                        throw new \Exception("Fila {$fila}: el impuesto '{$impuestoNombre}' no existe.");
                    }
                }

                /*
                 |------------------------------------------------------------
                 | BUSCAR PRODUCTO EXISTENTE
                 |------------------------------------------------------------
                 */
                $producto = Producto::whereRaw(
                    'LOWER(nombre) = ?',
                    [mb_strtolower($nombre)]
                )->first();

                if (!$producto) {
                    $producto = Producto::create([
                        'nombre'             => $nombre,
                        'descripcion'        => $descripcion !== '' ? $descripcion : null,
                        'precio'             => $precio,
                        'costo'              => $costo,
                        'stock'              => 0,
                        'stock_minimo'       => 0,
                        'stock_maximo'       => null,
                        'activo'             => $activo,
                        'subcategoria_id'    => $subcategoria->id,
                        'impuesto_id'        => $impuesto?->id,
                        'unidad_medida_id'   => $unidad?->id,
                        'imagen_path'        => null,
                        'mov_contable_segun' => Producto::MOV_SEGUN_ARTICULO,
                        'es_inventariable'   => $esInventariable,
                    ]);
                } else {
                    $producto->update([
                        'descripcion'        => $descripcion !== '' ? $descripcion : $producto->descripcion,
                        'precio'             => $precio,
                        'costo'              => $costo,
                        'activo'             => $activo,
                        'subcategoria_id'    => $subcategoria->id,
                        'impuesto_id'        => $impuesto?->id,
                        'unidad_medida_id'   => $unidad?->id,
                        'es_inventariable'   => $esInventariable,
                    ]);
                }

                /*
                 |------------------------------------------------------------
                 | BODEGA POR ID
                 |------------------------------------------------------------
                 */
                if ($esInventariable && !is_null($bodegaId)) {
                    $bodega = Bodega::where('id', $bodegaId)->first();

                    if (!$bodega) {
                        throw new \Exception("Fila {$fila}: la bodega '{$bodegaId}' no existe.");
                    }

                    $producto->bodegas()->syncWithoutDetaching([
                        $bodega->id => [
                            'stock'          => 0,
                            'stock_minimo'   => $stockMinimo,
                            'stock_maximo'   => $stockMaximo,
                            'costo_promedio' => 0,
                            'ultimo_costo'   => 0,
                            'metodo_costeo'  => 'PROMEDIO',
                        ],
                    ]);
                }
            }
        });
    }

    private function toBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = mb_strtolower(trim((string) $value));

        return in_array($value, [
            '1',
            'true',
            'si',
            'sí',
            'yes',
            'activo',
            'inventariable',
        ], true);
    }

    private function toFloat($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_string($value)) {
            $value = str_replace(['$', ' '], '', $value);
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }

    private function valorOpcionalNumerico($value, $default = null)
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_string($value)) {
            $value = str_replace(['$', ' '], '', $value);
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }
}