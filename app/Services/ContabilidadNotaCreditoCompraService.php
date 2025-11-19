<?php

namespace App\Services;

use App\Models\Asiento\Asiento;
use App\Models\NotaCredito;
use App\Models\Productos\Producto;
use App\Models\Productos\ProductoCuentaTipo;
use App\Models\CuentasContables\PlanCuentas;
use App\Models\Movimiento\Movimiento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ContabilidadNotaCreditoCompraService
{
    /**
     * Genera el asiento contable de una Nota Crédito de COMPRA.
     * Inversa de la factura de compra:
     *   DEBE   : Proveedores (CxP)   por el total NC
     *   HABER  : Inventario          por la base
     *   HABER  : IVA descontable     por el IVA compra
     *
     * @param  NotaCredito  $nc   NC de COMPRA (socio_negocio_id = proveedor)
     * @return Asiento
     */
    public static function asientoDesdeNotaCreditoCompra(NotaCredito $nc): Asiento
    {
        // 🔥 Carga relaciones necesarias - maneja tanto 'proveedor' como 'cliente'
        $relacionProveedor = method_exists($nc, 'proveedor') ? 'proveedor' : 'cliente';
        
        $nc->loadMissing([
            'detalles.producto.cuentas.cuentaPUC',
            'detalles.producto.cuentas.tipo',
            'detalles.producto.subcategoria.cuentas',
            'detalles.producto.impuesto',
            $relacionProveedor,
            'serie',
        ]);

        // Validaciones básicas
        if ($nc->detalles->isEmpty()) {
            throw new RuntimeException('La nota crédito de compra no tiene líneas.');
        }
        if (!$nc->fecha) {
            throw new RuntimeException('La nota crédito de compra no tiene fecha.');
        }

        // Asegura totales (por si aún no han sido recalculados)
        if (method_exists($nc, 'recalcularTotales')) {
            $nc->recalcularTotales()->save();
        }

        // Glosa bonita
        $numStr = $nc->prefijo
            ? ($nc->prefijo . '-' . str_pad((string)($nc->numero ?? $nc->id), ($nc->serie->longitud ?? 6), '0', STR_PAD_LEFT))
            : (string)($nc->numero ?? $nc->id);
        
        $proveedor = $nc->{$relacionProveedor} ?? null;
        $terceroNombre = $proveedor->razon_social ?? $proveedor->nombre ?? 'Proveedor #' . $nc->socio_negocio_id;
        $glosa = 'NC Compra ' . $numStr . ' · ' . $terceroNombre;

        return DB::transaction(function () use ($nc, $glosa, $relacionProveedor) {

            // ===============================
            // 1) Cabecera de asiento
            // ===============================
            $asiento = Asiento::create([
                'fecha'       => $nc->fecha,
                'tipo'        => 'NC_COMPRA',
                'glosa'       => $glosa,
                'origen'      => 'nota_credito_compra',
                'origen_id'   => $nc->id,
                'tercero_id'  => $nc->socio_negocio_id,
                'moneda'      => $nc->moneda ?? 'COP',
                'total_debe'  => 0,
                'total_haber' => 0,
            ]);

            $metaBase = [
                'nota_credito_id' => $nc->id,
                'tercero_id'      => $nc->socio_negocio_id,
            ];

            $totalDebe  = 0.0;
            $totalHaber = 0.0;

            // ============================================================
            // 2) Acumulados por cuenta: INVENTARIO (haber) e IVA compra (haber)
            // ============================================================
            $inventarioPorCuenta = [];
            $ivaPorCuentaTarifa  = [];

            foreach ($nc->detalles as $d) {
                $cant   = max(0, (float)$d->cantidad);
                $pu     = max(0, (float)$d->precio_unitario);
                $desc   = min(100, max(0, (float)$d->descuento_pct));
                $ivaPct = min(100, max(0, (float)$d->impuesto_pct));

                $base = round($cant * $pu * (1 - $desc / 100), 2);
                $iva  = round($base * $ivaPct / 100, 2);

                // ==== INVENTARIO (HABER): sale del inventario lo devuelto
                if ($d->producto_id && $base > 0) {
                    $p = $d->relationLoaded('producto') ? $d->producto : Producto::with([
                        'cuentas.cuentaPUC',
                        'cuentas.tipo',
                        'subcategoria.cuentas',
                    ])->find($d->producto_id);
                    
                    if (!$p) {
                        throw new RuntimeException("Producto {$d->producto_id} no existe.");
                    }

                    // 🔥 Resolver cuenta de INVENTARIO con fallback robusto
                    $ctaInv = self::resolveCuentaInventario($p, $d);
                    
                    if (!$ctaInv) {
                        throw new RuntimeException("Producto {$p->id} ({$p->nombre}) sin cuenta de INVENTARIO configurada.");
                    }

                    $inventarioPorCuenta[$ctaInv] = ($inventarioPorCuenta[$ctaInv] ?? 0) + $base;
                }

                // ==== IVA descontable (HABER): reversa del IVA compra
                if ($iva > 0 && $d->producto_id) {
                    $p = isset($p) && $p?->id === $d->producto_id 
                        ? $p 
                        : Producto::with('impuesto')->find($d->producto_id);

                    // 🔥 Resolver cuenta de IVA compra con fallback robusto
                    $ctaIva = self::resolveCuentaIVACompra($p, $d);
                    
                    if (!$ctaIva) {
                        throw new RuntimeException("No se pudo resolver la cuenta de IVA compra para el producto {$d->producto_id}.");
                    }

                    $ivaPorCuentaTarifa[$ctaIva][$ivaPct]['base'] = ($ivaPorCuentaTarifa[$ctaIva][$ivaPct]['base'] ?? 0) + $base;
                    $ivaPorCuentaTarifa[$ctaIva][$ivaPct]['iva']  = ($ivaPorCuentaTarifa[$ctaIva][$ivaPct]['iva']  ?? 0) + $iva;
                }
            }

            // ===============================
            // 3) HABER: inventario
            // ===============================
            foreach ($inventarioPorCuenta as $ctaInv => $monto) {
                $mov = ContabilidadService::post(
                    $asiento,
                    (int)$ctaInv,
                    0.0,
                    round($monto, 2),
                    'Reversa inventario por NC compra',
                    $metaBase
                );
                $totalDebe  += $mov->debito;
                $totalHaber += $mov->credito;
            }

            // ===============================
            // 4) HABER: IVA compra (descontable)
            // ===============================
            foreach ($ivaPorCuentaTarifa as $ctaIva => $porTarifa) {
                foreach ($porTarifa as $pct => $vals) {
                    $iva = round($vals['iva'] ?? 0, 2);
                    if ($iva <= 0) continue;

                    $mov = ContabilidadService::post(
                        $asiento,
                        (int)$ctaIva,
                        0.0,
                        $iva,
                        'Reversa IVA compra (NC)',
                        $metaBase + [
                            'base_gravable'  => round($vals['base'] ?? 0, 2),
                            'tarifa_pct'     => (float)$pct,
                            'valor_impuesto' => $iva,
                        ]
                    );
                    $totalDebe  += $mov->debito;
                    $totalHaber += $mov->credito;
                }
            }

            // ===================================================
            // 5) DEBE: Proveedores (CxP) por el TOTAL de la NC
            // ===================================================
            $totalNc = round((float)$nc->total, 2);
            if ($totalNc <= 0) {
                throw new RuntimeException('El total de la Nota Crédito de compra es cero.');
            }

            // 🔥 Resolver cuenta CxP Proveedores con fallback robusto
            $ctaCxp = self::resolveCuentaCxPProveedor($nc, $relacionProveedor);
            
            if (!$ctaCxp) {
                throw new RuntimeException('No se encontró una cuenta de Proveedores (CxP) para la NC de compra.');
            }

            $movDebe = ContabilidadService::post(
                $asiento,
                (int)$ctaCxp,
                $totalNc,
                0.0,
                'Compensación a Proveedores por NC compra',
                $metaBase
            );
            $totalDebe  += $movDebe->debito;
            $totalHaber += $movDebe->credito;

            // ===============================
            // 6) Cierre de totales del asiento
            // ===============================
            $asiento->update([
                'total_debe'  => round($totalDebe, 2),
                'total_haber' => round($totalHaber, 2),
            ]);

            // Check cuadratura
            if (round($totalDebe - $totalHaber, 2) !== 0.0) {
                Log::error('Asiento NC compra descuadrado', [
                    'nc_id' => $nc->id,
                    'debe'  => $totalDebe,
                    'haber' => $totalHaber,
                    'diff'  => round($totalDebe - $totalHaber, 2),
                ]);
                throw new RuntimeException('El asiento de la NC de compra no cuadra (diferencia: $' . round($totalDebe - $totalHaber, 2) . ').');
            }

            return $asiento;
        });
    }
private static function resolveCuentaInventario(Producto $p, $detalle): ?int
{
    // 0) Si la línea trae cuenta_devolucion_id explícita, la respetamos
    if (!empty($detalle->cuenta_devolucion_id) && PlanCuentas::whereKey($detalle->cuenta_devolucion_id)->exists()) {
        return (int) $detalle->cuenta_devolucion_id;
    }

    // 1) Si el detalle tiene cuenta_inventario_id directamente
    if (!empty($detalle->cuenta_inventario_id) && PlanCuentas::whereKey($detalle->cuenta_inventario_id)->exists()) {
        return (int) $detalle->cuenta_inventario_id;
    }

    /**
     * ============================================================
     * PRIORIDAD: GASTO_DEV (si está configurado en el producto
     * o en la subcategoría) -> esto hace que la NC use GASTO_DEV.
     * ============================================================
     */
    $tipoGastoDevId = cache()->remember(
        'producto_cuenta_tipo_gasto_dev_id',
        600,
        fn () => ProductoCuentaTipo::where('codigo', 'GASTO_DEV')->value('id')
    );

    if ($tipoGastoDevId) {
        // a) Según ARTÍCULO
        $cuenta = $p->relationLoaded('cuentas')
            ? $p->cuentas->firstWhere('tipo_id', (int) $tipoGastoDevId)
            : $p->cuentas()->where('tipo_id', (int) $tipoGastoDevId)->first();

        if ($cuenta && !empty($cuenta->plan_cuentas_id) && PlanCuentas::whereKey($cuenta->plan_cuentas_id)->exists()) {
            return (int) $cuenta->plan_cuentas_id;
        }

        // b) Según SUBCATEGORÍA
        if (!empty($p->subcategoria_id)) {
            $sc = \App\Models\Categorias\SubcategoriaCuenta::query()
                ->where('subcategoria_id', (int) $p->subcategoria_id)
                ->where('tipo_id', (int) $tipoGastoDevId)
                ->first();

            if ($sc && !empty($sc->plan_cuentas_id) && PlanCuentas::whereKey($sc->plan_cuentas_id)->exists()) {
                return (int) $sc->plan_cuentas_id;
            }
        }
    }

    /**
     * ============================================================
     * Si NO hay GASTO_DEV configurado, seguimos con la lógica
     * original de INVENTARIO.
     * ============================================================
     */

    // Detectar si el producto es inventariable
    $esInventariable = true;
    if (isset($p->inventariable)) {
        $esInventariable = (bool) $p->inventariable;
    } elseif (isset($p->maneja_inventario)) {
        $esInventariable = (bool) $p->maneja_inventario;
    } elseif (isset($p->tipo_articulo)) {
        $esInventariable = strtoupper((string) $p->tipo_articulo) !== 'SERVICIO';
    }

    // Si NO inventariable y tampoco hubo GASTO_DEV, intentamos otra vez
    if (!$tipoGastoDevId && !$esInventariable) {
        // algún gasto genérico 51 / 52
        $ctaGasto = PlanCuentas::query()
            ->where('cuenta_activa', 1)
            ->where(fn ($q) => $q->where('titulo', 0)->orWhereNull('titulo'))
            ->where(function ($q) {
                $q->where('codigo', 'like', '51%')
                  ->orWhere('codigo', 'like', '52%');
            })
            ->orderBy('codigo')
            ->value('id');

        if ($ctaGasto) {
            return (int) $ctaGasto;
        }
    }

    // 2. Si el producto tiene cuenta_inventario_id directa
    if (!empty($p->cuenta_inventario_id) && PlanCuentas::whereKey($p->cuenta_inventario_id)->exists()) {
        return (int) $p->cuenta_inventario_id;
    }

    // 3. Usar ContabilidadService si existe
    if (method_exists(ContabilidadService::class, 'cuentaSegunConfiguracion')) {
        $cta = ContabilidadService::cuentaSegunConfiguracion($p, 'INVENTARIO');
        if ($cta) return (int) $cta;
    }

    // 4. Buscar en cuentas del producto por tipo "INVENTARIO"
    $tipoInvId = cache()->remember(
        'producto_cuenta_tipo_inventario_id',
        600,
        fn () => ProductoCuentaTipo::where('codigo', 'INVENTARIO')->value('id')
    );

    if ($tipoInvId) {
        // Según ARTÍCULO
        if ($p->mov_contable_segun === 'ARTICULO' || $p->mov_contable_segun === Producto::MOV_SEGUN_ARTICULO) {
            $cuenta = $p->relationLoaded('cuentas')
                ? $p->cuentas->firstWhere('tipo_id', (int) $tipoInvId)
                : $p->cuentas()->where('tipo_id', (int) $tipoInvId)->first();

            if ($cuenta && !empty($cuenta->plan_cuentas_id)) {
                return (int) $cuenta->plan_cuentas_id;
            }
        }

        // Según SUBCATEGORÍA
        if (($p->mov_contable_segun === 'SUBCATEGORIA' || $p->mov_contable_segun === Producto::MOV_SEGUN_SUBCATEGORIA)
            && !empty($p->subcategoria_id)) {

            if ($p->relationLoaded('subcategoria') && $p->subcategoria?->relationLoaded('cuentas')) {
                $sc = $p->subcategoria->cuentas->firstWhere('tipo_id', (int) $tipoInvId);
                if ($sc && !empty($sc->plan_cuentas_id)) {
                    return (int) $sc->plan_cuentas_id;
                }
            }

            $sc = \App\Models\Categorias\SubcategoriaCuenta::query()
                ->where('subcategoria_id', (int) $p->subcategoria_id)
                ->where('tipo_id', (int) $tipoInvId)
                ->first();

            if ($sc && !empty($sc->plan_cuentas_id)) {
                return (int) $sc->plan_cuentas_id;
            }
        }
    }

    // 5. Fallback final: alguna cuenta genérica de inventario (14…)
    return PlanCuentas::query()
        ->where('cuenta_activa', 1)
        ->where(fn ($q) => $q->where('titulo', 0)->orWhereNull('titulo'))
        ->where('codigo', 'like', '14%')
        ->orderBy('codigo')
        ->value('id');
}


    
    /**
     * 🔥 Resolver cuenta de IVA compra con múltiples fallbacks
     */
    private static function resolveCuentaIVACompra(Producto $p, $detalle): ?int
    {
        // 1. Si el detalle tiene cuenta_iva_id directamente
        if (!empty($detalle->cuenta_iva_id) && PlanCuentas::whereKey($detalle->cuenta_iva_id)->exists()) {
            return (int) $detalle->cuenta_iva_id;
        }

        // 2. Usar ContabilidadService si existe
        if (method_exists(ContabilidadService::class, 'cuentaSegunConfiguracion')) {
            $cta = ContabilidadService::cuentaSegunConfiguracion($p, 'IVA');
            if ($cta) return (int) $cta;
        }

        // 3. Cuenta del propio impuesto
        $imp = $p?->impuesto;
        if ($imp && !empty($imp->cuenta_id) && PlanCuentas::whereKey($imp->cuenta_id)->exists()) {
            return (int) $imp->cuenta_id;
        }

        // 4. Fallback: buscar cualquier cuenta de IVA descontable (2408 en Colombia)
        return PlanCuentas::query()
            ->where('cuenta_activa', 1)
            ->where(fn($q) => $q->where('titulo', 0)->orWhereNull('titulo'))
            ->where(function ($q) {
                $q->where('codigo', 'like', '2408%')  // IVA descontable Colombia
                  ->orWhere('codigo', 'like', '1355%'); // Anticipo IVA
            })
            ->orderBy('codigo')
            ->value('id');
    }

    /**
     * 🔥 Resolver cuenta CxP Proveedores con múltiples fallbacks
     */
    private static function resolveCuentaCxPProveedor(NotaCredito $nc, string $relacionProveedor): ?int
    {
        // 1. Si el usuario seleccionó una cuenta explícita en la NC
        if (!empty($nc->cuenta_cobro_id) && PlanCuentas::whereKey($nc->cuenta_cobro_id)->exists()) {
            return (int) $nc->cuenta_cobro_id;
        }

        // 2. CxP del proveedor en sus cuentas
        if ($nc->relationLoaded($relacionProveedor)) {
            $proveedor = $nc->{$relacionProveedor};
            
            // Intentar varios campos posibles
            foreach (['cuenta_cxp_id', 'plan_cuenta_id', 'cuenta_id'] as $field) {
                if (!empty($proveedor->{$field}) && PlanCuentas::whereKey($proveedor->{$field})->exists()) {
                    return (int) $proveedor->{$field};
                }
            }

            // Si tiene relación cuentas
            if (method_exists($proveedor, 'cuentas')) {
                $cuentas = $proveedor->relationLoaded('cuentas') ? $proveedor->cuentas : $proveedor->cuentas()->first();
                if ($cuentas && !empty($cuentas->cuenta_cxp_id) && PlanCuentas::whereKey($cuentas->cuenta_cxp_id)->exists()) {
                    return (int) $cuentas->cuenta_cxp_id;
                }
            }
        }

        // 3. Fallback por clase de cuenta
        return PlanCuentas::query()
            ->where('clase_cuenta', 'CXP_PROVEEDORES')
            ->where('cuenta_activa', 1)
            ->where(fn($q) => $q->where('titulo', 0)->orWhereNull('titulo'))
            ->orderBy('codigo')
            ->value('id');
    }

    public static function revertirAsientoNotaCreditoCompra(NotaCredito $nc): void
    {
        if (!$nc->id) {
            throw new RuntimeException('La nota crédito de compra no tiene ID.');
        }

        DB::transaction(function () use ($nc) {
            // Ajusta estos campos según tu modelo de Asiento 👇
            $asientos = Asiento::query()
                ->where('documento', 'NOTA_CREDITO_COMPRA')   // o el código que uses
                ->where('documento_id', $nc->id)
                ->get();

            if ($asientos->isEmpty()) {
                // No hay asiento que revertir: salimos suave
                Log::warning('No se encontraron asientos para revertir en NC compra', [
                    'nota_credito_id' => $nc->id,
                ]);
                return;
            }

            foreach ($asientos as $a) {
                // Si ya está anulado/revertido, lo saltamos
                if (in_array($a->estado, ['ANULADO','REVERTIDO'], true) || $a->anulado) {
                    continue;
                }

                // Creamos asiento de reversa
                $asientoReverso = Asiento::create([
                    'fecha'        => $nc->fecha ?? now(),
                    'glosa'        => 'Reverso NC COMPRA #'.$nc->id.' (Asiento '.$a->id.')',
                    'moneda'       => $a->moneda ?? 'COP',
                    'documento'    => 'REVERSO_NC_COMPRA',
                    'documento_id' => $nc->id,
                    'estado'       => 'EMITIDO',
                    'anulado'      => 0,
                    // si usas campos como empresa_id, usuario_id, etc. añádelos acá
                ]);

                // Invertimos movimientos
                $a->loadMissing('movimientos');

                foreach ($a->movimientos as $m) {
                    Movimiento::create([
                        'asiento_id'  => $asientoReverso->id,
                        'cuenta_id'   => $m->cuenta_id,
                        'detalle'     => 'Reverso: '.$m->detalle,
                        'debe'        => (float)$m->haber,
                        'haber'       => (float)$m->debe,
                        'centro_costo_id' => $m->centro_costo_id ?? null,
                        // agrega aquí cualquier otro campo obligatorio de tu tabla movimientos
                    ]);
                }

                // Marcamos el asiento original como revertido/anulado
                $a->estado  = 'REVERTIDO';
                $a->anulado = 1;
                $a->save();
            }
        });
    }
}