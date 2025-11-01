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
use Illuminate\Database\Eloquent\Collection;

class KardexProducto extends Component
{
    use WithPagination;

    /** Tailwind para la paginación */
    protected string $paginationTheme = 'tailwind';

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
    // ---------------------------------------------------------
    // 0) Reset de saldos
    // ---------------------------------------------------------
    $this->saldoInicialCant = 0.0;
    $this->saldoInicialVal  = 0.0;
    $this->saldoFinalCant   = 0.0;
    $this->saldoFinalVal    = 0.0;

    // Sanitiza perPage por si viene raro desde la UI
    $this->perPage = in_array((int)$this->perPage, [10,25,50,100], true) ? (int)$this->perPage : 10;

    // ---------------------------------------------------------
    // 1) Obtener movimientos paginados (o paginator vacío)
    // ---------------------------------------------------------
    if ($this->producto_id) {
        // Tus métodos existentes
        $this->calcularSaldoInicial();
        $filas = $this->kardexEnRangoPaginado(); // Debe devolver LengthAwarePaginator
    } else {
        // Paginador vacío para mantener {{ $filas->links() }} y estructura
        $filas = new LengthAwarePaginator(
            collect(),
            0,
            $this->perPage,
            1,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    // ---------------------------------------------------------
    // 2) Construir grupos por documento para el acordeón
    // ---------------------------------------------------------
    // Asegúrate que cada ítem del paginator sea un array (o conviértelo).
    $items = collect($filas->items() ?? [])
        ->map(function ($r) {
            // Normaliza a array si viene como objeto
            return is_array($r) ? $r : (is_object($r) ? (array)$r : []);
        });

    $grupos = $items
        ->groupBy(function (array $r) {
            // Intento 1: doc_tipo + doc_id (lo ideal para un ID estable)
            $docTipo = trim((string)($r['doc_tipo'] ?? ''));
            $docId   = trim((string)($r['doc_id']   ?? ''));
            $uid     = trim($docTipo . '-' . $docId, '- ');

            if ($uid !== '') {
                return $uid;
            }

            // Intento 2: hash estable basado en doc + fecha (fallback)
            $doc   = (string)($r['doc']   ?? 'Documento');
            $fecha = (string)($r['fecha'] ?? '—');
            return md5($doc . '|' . $fecha);
        })
        ->map(function (Collection $rows) {
            $h = (array)$rows->first();

            // Recalcular uid para tenerlo disponible en la vista
            $docTipo = trim((string)($h['doc_tipo'] ?? ''));
            $docId   = trim((string)($h['doc_id']   ?? ''));
            $uid     = trim($docTipo . '-' . $docId, '- ');
            if ($uid === '') {
                $uid = md5(((string)($h['doc'] ?? 'Documento')) . '|' . ((string)($h['fecha'] ?? '—')));
            }

            return [
                'uid'           => $uid,
                'doc'           => (string)($h['doc']   ?? 'Documento'),
                'fecha'         => (string)($h['fecha'] ?? '—'),
                'bodega'        => (string)($h['bodega'] ?? '—'),
                'entrada_total' => (float)$rows->sum(fn($x) => (float) ((array)$x)['entrada'] ?? 0),
                'salida_total'  => (float)$rows->sum(fn($x) => (float) ((array)$x)['salida']  ?? 0),
                'rows'          => $rows->values()->map(fn($x) => (array)$x), // líneas del documento
            ];
        })
        ->values();

    // ---------------------------------------------------------
    // 3) (Opcional) Actualiza saldos finales si procede aquí
    // ---------------------------------------------------------
    // Si tus métodos ya devuelven estos valores, deja esto comentado.
    // $this->saldoFinalCant = ...;
    // $this->saldoFinalVal  = ...;

    // ---------------------------------------------------------
    // 4) Retornar a la vista
    // ---------------------------------------------------------
    return view('livewire.productos.kardex-producto', [
        'filas'  => $filas,   // paginator original (para links)
        'grupos' => $grupos,  // grupos por documento (para el acordeón)
    ]);
}

    /* =======================================================
     * CÁLCULOS
     * ======================================================= */

    /** Suma entradas y salidas (a costo promedio móvil) ANTES del rango. */
    private function calcularSaldoInicial(): void
    {
        $hasta = $this->desde ? Carbon::parse($this->desde)->startOfDay() : null;

        $movimientos = collect();

        // KardexMovimiento
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

        // ProductoCostoMovimiento
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

        // Orden cronológico
        $movimientos = $movimientos->sortBy('fecha')->values();

        // Rolling
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

    /** Lista movimientos del rango (ambas tablas) y calcula saldos corridos. */
    private function kardexEnRangoPaginado(): LengthAwarePaginator
    {
        $desde = $this->desde ? Carbon::parse($this->desde)->startOfDay() : null;
        $hasta = $this->hasta ? Carbon::parse($this->hasta)->endOfDay()   : null;

        $movimientos = collect();

        // 1) KardexMovimiento
        if (in_array($this->fuenteDatos, ['kardex', 'ambas'])) {
            $qKardex = KardexMovimiento::query()
                ->with('tipoDocumento:id,codigo,nombre')
                ->where('producto_id', $this->producto_id);

            if ($this->bodega_id) $qKardex->where('bodega_id', $this->bodega_id);
            if ($desde)           $qKardex->where('fecha', '>=', $desde);
            if ($hasta)           $qKardex->where('fecha', '<=', $hasta);

            if (trim($this->buscarDoc) !== '') {
                $txt = '%' . (string) Str::of($this->buscarDoc)->trim() . '%';
                $qKardex->where(function ($x) use ($txt) {
                    $x->orWhere('doc_id', 'like', $txt)
                      ->orWhere('ref', 'like', $txt)
                      ->orWhereHas('tipoDocumento', fn($q) =>
                          $q->where('nombre','like',$txt)
                            ->orWhere('codigo','like',$txt)
                      );
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

        // 2) ProductoCostoMovimiento
        if (in_array($this->fuenteDatos, ['costos', 'ambas'])) {
            $qCostos = ProductoCostoMovimiento::query()
                ->with('tipoDocumento:id,codigo,nombre')
                ->where('producto_id', $this->producto_id);

            if ($this->bodega_id) $qCostos->where('bodega_id', $this->bodega_id);
            if ($desde)           $qCostos->where('fecha', '>=', $desde);
            if ($hasta)           $qCostos->where('fecha', '<=', $hasta);

            if (trim($this->buscarDoc) !== '') {
                $txt = '%' . (string) Str::of($this->buscarDoc)->trim() . '%';
                $qCostos->where(function ($x) use ($txt) {
                    $x->orWhere('doc_id', 'like', $txt)
                      ->orWhere('ref', 'like', $txt)
                      ->orWhereHas('tipoDocumento', fn($q) =>
                          $q->where('nombre','like',$txt)
                            ->orWhere('codigo','like',$txt)
                      );
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

        // 3) Orden estable por fecha e id real
        $movimientos = $movimientos->sortBy([
            ['fecha', 'asc'],
            ['model_id', 'asc'],
        ])->values();

        // 4) Paginación manual
        $total = $movimientos->count();
        $currentPage = (int) ($this->page ?? \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1);
        $currentPage = max(1, $currentPage);
        $offset = ($currentPage - 1) * $this->perPage;
        $itemsPagina = $movimientos->slice($offset, $this->perPage)->values();

        // 5) Saldos corridos (acumular previos a la página)
        [$saldoCant, $saldoVal] = [$this->saldoInicialCant, $this->saldoInicialVal];

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

        // 6) Construir items de la página con saldos
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
            $ref = $m['ref'] ?? null;
            if ($ref) $docText .= ' ('.$ref.')';
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

            if ($m['fuente'] === 'costos') {
                $item['costo_historico'] = [
                    'metodo_costeo'          => $m['metodo_costeo'] ?? null,
                    'costo_prom_anterior'    => $m['costo_prom_anterior'] ?? null,
                    'costo_prom_nuevo'       => $m['costo_prom_nuevo'] ?? null,
                    'ultimo_costo_anterior'  => $m['ultimo_costo_anterior'] ?? null,
                    'ultimo_costo_nuevo'     => $m['ultimo_costo_nuevo'] ?? null,
                    'tipo_evento'            => $m['tipo_evento'] ?? null,
                    'valor_mov'              => $m['valor_mov'] ?? null,
                ];
            } else {
                $item['costo_historico'] = null;
            }

            return $item;
        });

        // 7) Saldos finales (última página)
        if ($currentPage === (int) ceil($total / $this->perPage) && $items->count()) {
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
