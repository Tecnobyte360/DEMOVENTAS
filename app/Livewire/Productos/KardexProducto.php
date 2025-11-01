<?php

namespace App\Livewire\Productos;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\Productos\Producto;
use App\Models\Bodega;
use App\Models\Movimiento\KardexMovimiento;
use App\Models\Movimiento\ProductoCostoMovimiento;
use App\Models\TiposDocumento\TipoDocumento;

class KardexProducto extends Component
{
    use WithPagination;

    /** ==== Filtros ==== */
    public ?int $producto_id = null;
    public ?int $bodega_id   = null;
    public ?string $desde    = null;   // YYYY-MM-DD
    public ?string $hasta    = null;   // YYYY-MM-DD
    public string $buscarDoc = '';
    public int $perPage      = 25;
    
    /** ==== Opciones de vista ==== */
    public string $fuenteDatos = 'kardex'; // 'kardex' | 'costos' | 'ambas'

    /** ==== Saldos ==== */
    public float $saldoInicialCant = 0.0;
    public float $saldoInicialVal  = 0.0;
    public float $saldoFinalCant   = 0.0;
    public float $saldoFinalVal    = 0.0;

    /** ==== Catálogos ==== */
    public $productos;
    public $bodegas;

    protected $queryString = [
        'producto_id' => ['except' => null],
        'bodega_id'   => ['except' => null],
        'desde'       => ['except' => null],
        'hasta'       => ['except' => null],
        'buscarDoc'   => ['except' => ''],
        'perPage'     => ['except' => 25],
        'fuenteDatos' => ['except' => 'kardex'],
    ];

    public function mount(?int $productoId = null): void
    {
        $this->productos = Producto::orderBy('nombre')->get(['id','nombre']);
        $this->bodegas   = Bodega::orderBy('nombre')->get(['id','nombre']);

        $this->producto_id = $productoId ?: $this->producto_id;
        $this->desde = $this->desde ?: Carbon::now()->subDays(90)->toDateString();
        $this->hasta = $this->hasta ?: Carbon::now()->toDateString();
    }

    public function updating($prop): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $this->saldoInicialCant = $this->saldoInicialVal = 0.0;
        $this->saldoFinalCant   = $this->saldoFinalVal   = 0.0;

        if ($this->producto_id) {
            $this->calcularSaldoInicial();
            $filas = $this->kardexEnRangoPaginado();
        } else {
            $filas = new LengthAwarePaginator(collect(), 0, $this->perPage, 1, [
                'path' => request()->url(), 'query' => request()->query()
            ]);
        }

        return view('livewire.productos.kardex-producto', compact('filas'));
    }

    /* =======================================================
     * CÁLCULOS
     * ======================================================= */

    /** Suma entradas y salidas (a costo promedio móvil) ANTES del rango. */
    private function calcularSaldoInicial(): void
    {
        $hasta = $this->desde ? Carbon::parse($this->desde)->startOfDay() : null;

        // Usamos ambas tablas para calcular el saldo inicial
        $movimientos = collect();

        // Movimientos de KardexMovimiento
        if (in_array($this->fuenteDatos, ['kardex', 'ambas'])) {
            $qKardex = KardexMovimiento::query()
                ->where('producto_id', $this->producto_id);

            if ($this->bodega_id) $qKardex->where('bodega_id', $this->bodega_id);
            if ($hasta)           $qKardex->where('fecha', '<', $hasta);

            $kardexMovs = $qKardex->orderBy('fecha')->orderBy('id')
                ->get(['id','fecha','entrada','salida','cantidad','signo','costo_unitario','total'])
                ->map(function($m) {
                    return [
                        'id' => 'k_'.$m->id,
                        'fecha' => $m->fecha,
                        'entrada' => (float)($m->entrada ?? 0),
                        'salida' => (float)($m->salida ?? 0),
                        'cantidad' => (float)($m->cantidad ?? 0),
                        'signo' => (int)($m->signo ?? 0),
                        'costo_unitario' => (float)($m->costo_unitario ?? 0),
                        'fuente' => 'kardex'
                    ];
                });

            $movimientos = $movimientos->merge($kardexMovs);
        }

        // Movimientos de ProductoCostoMovimiento
        if (in_array($this->fuenteDatos, ['costos', 'ambas'])) {
            $qCostos = ProductoCostoMovimiento::query()
                ->where('producto_id', $this->producto_id);

            if ($this->bodega_id) $qCostos->where('bodega_id', $this->bodega_id);
            if ($hasta)           $qCostos->where('fecha', '<', $hasta);

            $costosMovs = $qCostos->orderBy('fecha')->orderBy('id')
                ->get(['id','fecha','cantidad','costo_unit_mov'])
                ->map(function($m) {
                    $cant = (float)($m->cantidad ?? 0);
                    return [
                        'id' => 'c_'.$m->id,
                        'fecha' => $m->fecha,
                        'entrada' => $cant > 0 ? $cant : 0,
                        'salida' => $cant < 0 ? abs($cant) : 0,
                        'cantidad' => $cant,
                        'signo' => $cant >= 0 ? 1 : -1,
                        'costo_unitario' => (float)($m->costo_unit_mov ?? 0),
                        'fuente' => 'costos'
                    ];
                });

            $movimientos = $movimientos->merge($costosMovs);
        }

        // Ordenar todos los movimientos por fecha
        $movimientos = $movimientos->sortBy('fecha')->values();

        $cant = 0.0; $val = 0.0;
        foreach ($movimientos as $m) {
            $entrada = $m['entrada'];
            $salida  = $m['salida'];
            $tipo    = $entrada > 0 ? 'ENTRADA' : ($salida > 0 ? 'SALIDA' : ($m['signo'] >= 0 ? 'ENTRADA' : 'SALIDA'));
            $c       = $tipo === 'ENTRADA' ? max($entrada, abs($m['cantidad'])) : max($salida, abs($m['cantidad']));

            if ($tipo === 'ENTRADA') {
                $cu  = $m['costo_unitario'];
                $val += $c * $cu;
                $cant += $c;
            } else {
                $cpu = $cant > 0 ? ($val / max($cant, 1e-9)) : 0.0;
                $cant -= $c;
                $val  -= $c * $cpu;
                if ($cant < 1e-9) { $cant = 0.0; $val = 0.0; }
            }
        }

        $this->saldoInicialCant = round($cant, 6);
        $this->saldoInicialVal  = round($val, 2);
    }

    /** Lista movimientos del rango de AMBAS tablas y calcula saldos corridos. */
    private function kardexEnRangoPaginado(): LengthAwarePaginator
    {
        $desde = $this->desde ? Carbon::parse($this->desde)->startOfDay() : null;
        $hasta = $this->hasta ? Carbon::parse($this->hasta)->endOfDay()   : null;

        // Colección unificada de movimientos
        $movimientos = collect();

        // 1. Obtener movimientos de KardexMovimiento
        if (in_array($this->fuenteDatos, ['kardex', 'ambas'])) {
            $qKardex = KardexMovimiento::query()
                ->with('tipoDocumento:id,codigo,nombre')
                ->where('producto_id', $this->producto_id);

            if ($this->bodega_id) $qKardex->where('bodega_id', $this->bodega_id);
            if ($desde)           $qKardex->where('fecha', '>=', $desde);
            if ($hasta)           $qKardex->where('fecha', '<=', $hasta);

            if (trim($this->buscarDoc) !== '') {
                $txt = '%' . Str::of($this->buscarDoc)->trim() . '%';
                $qKardex->where(function ($x) use ($txt) {
                    $x->orWhere('doc_id', 'like', $txt)
                      ->orWhere('ref', 'like', $txt)
                      ->orWhereHas('tipoDocumento', fn($q)=>$q->where('nombre','like',$txt)->orWhere('codigo','like',$txt));
                });
            }

            $kardexMovs = $qKardex->orderBy('fecha')->orderBy('id')
                ->get()
                ->map(function($m) {
                    return [
                        'id' => 'k_'.$m->id,
                        'model_id' => $m->id,
                        'fecha' => $m->fecha,
                        'bodega_id' => $m->bodega_id,
                        'tipo_documento_id' => $m->tipo_documento_id,
                        'doc_id' => $m->doc_id,
                        'ref' => $m->ref,
                        'tipo_documento' => $m->tipoDocumento,
                        'entrada' => (float)($m->entrada ?? 0),
                        'salida' => (float)($m->salida ?? 0),
                        'cantidad' => (float)($m->cantidad ?? 0),
                        'signo' => (int)($m->signo ?? 0),
                        'costo_unitario' => (float)($m->costo_unitario ?? 0),
                        'fuente' => 'kardex',
                        'raw' => $m
                    ];
                });

            $movimientos = $movimientos->merge($kardexMovs);
        }

        // 2. Obtener movimientos de ProductoCostoMovimiento
        if (in_array($this->fuenteDatos, ['costos', 'ambas'])) {
            $qCostos = ProductoCostoMovimiento::query()
                ->with('tipoDocumento:id,codigo,nombre')
                ->where('producto_id', $this->producto_id);

            if ($this->bodega_id) $qCostos->where('bodega_id', $this->bodega_id);
            if ($desde)           $qCostos->where('fecha', '>=', $desde);
            if ($hasta)           $qCostos->where('fecha', '<=', $hasta);

            if (trim($this->buscarDoc) !== '') {
                $txt = '%' . Str::of($this->buscarDoc)->trim() . '%';
                $qCostos->where(function ($x) use ($txt) {
                    $x->orWhere('doc_id', 'like', $txt)
                      ->orWhere('ref', 'like', $txt)
                      ->orWhereHas('tipoDocumento', fn($q)=>$q->where('nombre','like',$txt)->orWhere('codigo','like',$txt));
                });
            }

            $costosMovs = $qCostos->orderBy('fecha')->orderBy('id')
                ->get()
                ->map(function($m) {
                    $cant = (float)($m->cantidad ?? 0);
                    return [
                        'id' => 'c_'.$m->id,
                        'model_id' => $m->id,
                        'fecha' => $m->fecha,
                        'bodega_id' => $m->bodega_id,
                        'tipo_documento_id' => $m->tipo_documento_id,
                        'doc_id' => $m->doc_id,
                        'ref' => $m->ref,
                        'tipo_documento' => $m->tipoDocumento,
                        'entrada' => $cant > 0 ? $cant : 0,
                        'salida' => $cant < 0 ? abs($cant) : 0,
                        'cantidad' => $cant,
                        'signo' => $cant >= 0 ? 1 : -1,
                        'costo_unitario' => (float)($m->costo_unit_mov ?? 0),
                        'fuente' => 'costos',
                        'metodo_costeo' => $m->metodo_costeo,
                        'costo_prom_anterior' => $m->costo_prom_anterior,
                        'costo_prom_nuevo' => $m->costo_prom_nuevo,
                        'ultimo_costo_anterior' => $m->ultimo_costo_anterior,
                        'ultimo_costo_nuevo' => $m->ultimo_costo_nuevo,
                        'tipo_evento' => $m->tipo_evento,
                        'valor_mov' => $m->valor_mov,
                        'raw' => $m
                    ];
                });

            $movimientos = $movimientos->merge($costosMovs);
        }

        // 3. Ordenar todos los movimientos por fecha e id
        $movimientos = $movimientos->sortBy([
            ['fecha', 'asc'],
            ['id', 'asc']
        ])->values();

        // 4. Paginar manualmente
        $total = $movimientos->count();
        $currentPage = $this->getPage();
        $offset = ($currentPage - 1) * $this->perPage;
        $itemsPagina = $movimientos->slice($offset, $this->perPage)->values();

        // 5. Calcular saldos corridos desde el inicio de la página
        [$saldoCant, $saldoVal] = [$this->saldoInicialCant, $this->saldoInicialVal];

        // Recorrer movimientos ANTES de la página actual
        if ($offset > 0) {
            $prevMovs = $movimientos->slice(0, $offset);
            foreach ($prevMovs as $m) {
                $tipo = $m['entrada'] > 0 ? 'ENTRADA' : 'SALIDA';
                $c    = $tipo === 'ENTRADA' ? $m['entrada'] : $m['salida'];

                if ($tipo === 'ENTRADA') {
                    $cu = $m['costo_unitario'];
                    $saldoVal  += $c * $cu;
                    $saldoCant += $c;
                } else {
                    $cpu = $saldoCant > 0 ? ($saldoVal / max($saldoCant, 1e-9)) : 0.0;
                    $saldoCant -= $c;
                    $saldoVal  -= $c * $cpu;
                    if ($saldoCant < 1e-9) { $saldoCant = 0.0; $saldoVal = 0.0; }
                }
            }
        }

        // 6. Construir items de la página con saldos
        $items = $itemsPagina->map(function ($m) use (&$saldoCant, &$saldoVal) {
            $tipo = $m['entrada'] > 0 ? 'ENTRADA' : 'SALIDA';
            $c    = $tipo === 'ENTRADA' ? $m['entrada'] : $m['salida'];

            $cpu = null;
            if ($tipo === 'ENTRADA') {
                $cu = $m['costo_unitario'];
                $saldoVal  += $c * $cu;
                $saldoCant += $c;
            } else {
                $cpu = $saldoCant > 0 ? ($saldoVal / max($saldoCant, 1e-9)) : 0.0;
                $saldoCant -= $c;
                $saldoVal  -= $c * $cpu;
                if ($saldoCant < 1e-9) { $saldoCant = 0.0; $saldoVal = 0.0; }
            }

            $cpuSaldo = $saldoCant > 0 ? ($saldoVal / max($saldoCant, 1e-9)) : null;

            $bodegaNombre = optional($this->bodegas->firstWhere('id', $m['bodega_id']))->nombre;
            $docText = trim(
                ($m['tipo_documento']?->codigo ?? $m['tipo_documento']?->nombre ?? '') . ' ' .
                ($m['doc_id'] ? '#'.$m['doc_id'] : '')
            );
            if ($m['ref']) $docText .= ' ('.$m['ref'].')';
            $docText = $docText ?: '—';

            $item = [
                'id'         => $m['id'],
                'fecha'      => ($m['fecha'] instanceof Carbon ? $m['fecha']->format('Y-m-d H:i') : (string)$m['fecha']),
                'bodega'     => $bodegaNombre ?: '—',
                'doc'        => $docText,
                'tipo'       => $tipo,
                'entrada'    => $tipo === 'ENTRADA' ? $c : null,
                'salida'     => $tipo === 'SALIDA'  ? $c : null,
                'costo_unit' => $tipo === 'ENTRADA' ? $m['costo_unitario'] : ($cpu ?? 0.0),
                'saldo_cant' => round($saldoCant, 6),
                'saldo_val'  => round($saldoVal, 2),
                'saldo_cpu'  => $cpuSaldo !== null ? round($cpuSaldo, 6) : null,
                'fuente'     => $m['fuente'],
            ];

            // Agregar datos de costos si vienen de ProductoCostoMovimiento
            if ($m['fuente'] === 'costos') {
                $item['costo_historico'] = [
                    'metodo_costeo' => $m['metodo_costeo'] ?? null,
                    'costo_prom_anterior' => $m['costo_prom_anterior'] ?? null,
                    'costo_prom_nuevo' => $m['costo_prom_nuevo'] ?? null,
                    'ultimo_costo_anterior' => $m['ultimo_costo_anterior'] ?? null,
                    'ultimo_costo_nuevo' => $m['ultimo_costo_nuevo'] ?? null,
                    'tipo_evento' => $m['tipo_evento'] ?? null,
                    'valor_mov' => $m['valor_mov'] ?? null,
                ];
            } else {
                $item['costo_historico'] = null;
            }

            return $item;
        });

        // 7. Saldos finales
        if ($currentPage === (int)ceil($total / $this->perPage) && $items->count()) {
            $last = $items->last();
            $this->saldoFinalCant = (float) $last['saldo_cant'];
            $this->saldoFinalVal  = (float) $last['saldo_val'];
        }

        return new LengthAwarePaginator(
            $items,
            $total,
            $this->perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}