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

class KardexProducto extends Component
{
    use WithPagination;

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
        // Reset saldos mostrados
        $this->saldoInicialCant = 0.0;
        $this->saldoInicialVal  = 0.0;
        $this->saldoFinalCant   = 0.0;
        $this->saldoFinalVal    = 0.0;

        // Sanitiza perPage
        $this->perPage = in_array((int)$this->perPage, [10,25,50,100], true) ? (int)$this->perPage : 10;

        if ($this->producto_id) {
            $this->calcularSaldoInicial();
            $filas = $this->kardexEnRangoPaginado();
        } else {
            $filas = new LengthAwarePaginator(
                collect(),
                0,
                $this->perPage,
                1,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        // Agrupación por doc para acordeón (sobre los items de la página)
        $items = collect($filas->items() ?? [])->map(fn($r) => is_array($r) ? $r : (array)$r);

        $grupos = $items
            ->groupBy(function ($r) {
                $docTipo = trim((string)($r['doc_tipo'] ?? ''));
                $docId   = trim((string)($r['doc_id'] ?? ''));
                $uid     = trim($docTipo . '-' . $docId, '- ');
                return $uid !== '' ? $uid : md5(($r['doc'] ?? 'Documento') . '|' . ($r['fecha'] ?? ''));
            })
            ->map(function ($rows) {
                $rows = collect($rows);
                $h = (array) $rows->first();

                $docTipo = trim((string)($h['doc_tipo'] ?? ''));
                $docId   = trim((string)($h['doc_id'] ?? ''));
                $uid     = trim($docTipo . '-' . $docId, '- ');
                if ($uid === '') {
                    $uid = md5(((string)($h['doc'] ?? 'Documento')) . '|' . ((string)($h['fecha'] ?? '—')));
                }

                return [
                    'uid'           => $uid,
                    'doc'           => (string)($h['doc']   ?? 'Documento'),
                    'fecha'         => (string)($h['fecha'] ?? '—'),
                    'bodega'        => (string)($h['bodega'] ?? '—'),
                    'entrada_total' => (float)$rows->sum(fn($x) => (float)($x['entrada'] ?? 0)),
                    'salida_total'  => (float)$rows->sum(fn($x) => (float)($x['salida']  ?? 0)),
                    'rows'          => $rows->values()->map(fn($x) => (array)$x),
                ];
            })
            ->values();

        return view('livewire.productos.kardex-producto', [
            'filas'  => $filas,
            'grupos' => $grupos,
        ]);
    }

    /* =======================================================
     * CÁLCULOS
     * ======================================================= */

    /** Suma entradas/salidas (costo promedio móvil) ANTES del rango. */
    private function calcularSaldoInicial(): void
    {
        $hasta = $this->desde ? Carbon::parse($this->desde)->startOfDay() : null;

        $movs = collect();

        if (in_array($this->fuenteDatos, ['kardex', 'ambas'])) {
            $q = KardexMovimiento::query()
                ->where('producto_id', $this->producto_id);

            if ($this->bodega_id) $q->where('bodega_id', $this->bodega_id);
            if ($hasta)           $q->where('fecha', '<', $hasta);

            $movs = $movs->merge(
                $q->orderBy('fecha')->orderBy('id')
                  ->get(['id','fecha','entrada','salida','cantidad','signo','costo_unitario'])
                  ->map(fn($m) => [
                      'id' => 'k_'.$m->id,
                      'fecha' => $m->fecha,
                      'entrada' => (float)($m->entrada ?? 0),
                      'salida'  => (float)($m->salida  ?? 0),
                      'cantidad' => (float)($m->cantidad ?? 0),
                      'signo'   => (int)($m->signo ?? 0),
                      'costo_unitario' => (float)($m->costo_unitario ?? 0),
                  ])
            );
        }

        if (in_array($this->fuenteDatos, ['costos', 'ambas'])) {
            $q = ProductoCostoMovimiento::query()
                ->where('producto_id', $this->producto_id);

            if ($this->bodega_id) $q->where('bodega_id', $this->bodega_id);
            if ($hasta)           $q->where('fecha', '<', $hasta);

            $movs = $movs->merge(
                $q->orderBy('fecha')->orderBy('id')
                  ->get(['id','fecha','cantidad','costo_unit_mov'])
                  ->map(function ($m) {
                      $cant = (float)($m->cantidad ?? 0);
                      return [
                          'id' => 'c_'.$m->id,
                          'fecha' => $m->fecha,
                          'entrada' => $cant > 0 ? $cant : 0,
                          'salida'  => $cant < 0 ? abs($cant) : 0,
                          'cantidad'=> $cant,
                          'signo'   => $cant >= 0 ? 1 : -1,
                          'costo_unitario' => (float)($m->costo_unit_mov ?? 0),
                      ];
                  })
            );
        }

        $movs = $movs->sortBy('fecha')->values();

        $cant = 0.0; $val = 0.0;
        foreach ($movs as $m) {
            $entrada = $m['entrada'];
            $salida  = $m['salida'];
            $tipo    = $entrada > 0 ? 'ENTRADA' : ($salida > 0 ? 'SALIDA' : ($m['signo'] >= 0 ? 'ENTRADA' : 'SALIDA'));
            $c       = $tipo === 'ENTRADA' ? max($entrada, abs($m['cantidad'])) : max($salida, abs($m['cantidad']));

            if ($tipo === 'ENTRADA') {
                $val  += $c * $m['costo_unitario'];
                $cant += $c;
            } else {
                $cpu  = $cant > 0 ? ($val / max($cant, 1e-9)) : 0.0;
                $cant -= $c;
                $val  -= $c * $cpu;
                if ($cant < 1e-9) { $cant = 0.0; $val = 0.0; }
            }
        }

        $this->saldoInicialCant = round($cant, 6);
        $this->saldoInicialVal  = round($val, 2);
    }

    /** Movimientos del rango (ambas tablas), saldo final global y paginación. */
    private function kardexEnRangoPaginado(): LengthAwarePaginator
    {
        $desde = $this->desde ? Carbon::parse($this->desde)->startOfDay() : null;
        $hasta = $this->hasta ? Carbon::parse($this->hasta)->endOfDay()   : null;

        $movs = collect();

        // Kardex
        if (in_array($this->fuenteDatos, ['kardex', 'ambas'])) {
            $q = KardexMovimiento::query()
                ->with('tipoDocumento:id,codigo,nombre')
                ->where('producto_id', $this->producto_id);

            if ($this->bodega_id) $q->where('bodega_id', $this->bodega_id);
            if ($desde)           $q->where('fecha', '>=', $desde);
            if ($hasta)           $q->where('fecha', '<=', $hasta);

            if (trim($this->buscarDoc) !== '') {
                $txt = '%' . (string) Str::of($this->buscarDoc)->trim() . '%';
                $q->where(function ($x) use ($txt) {
                    $x->orWhere('doc_id', 'like', $txt)
                      ->orWhere('ref', 'like', $txt)
                      ->orWhereHas('tipoDocumento', fn($q2) =>
                          $q2->where('nombre','like',$txt)->orWhere('codigo','like',$txt)
                      );
                });
            }

            $movs = $movs->merge(
                $q->orderBy('fecha')->orderBy('id')->get()->map(function ($m) {
                    return [
                        'id' => 'k_'.$m->id,
                        'model_id' => $m->id,
                        'fecha' => $m->fecha,
                        'bodega_id' => $m->bodega_id,
                        'tipo_documento_id' => $m->tipo_documento_id,
                        'doc_tipo' => optional($m->tipoDocumento)->codigo ?: optional($m->tipoDocumento)->nombre,
                        'doc_id' => $m->doc_id,
                        'ref' => $m->ref,
                        'tipo_documento' => $m->tipoDocumento,
                        'entrada' => (float)($m->entrada ?? 0),
                        'salida'  => (float)($m->salida  ?? 0),
                        'cantidad'=> (float)($m->cantidad ?? 0),
                        'signo'   => (int)($m->signo ?? 0),
                        'costo_unitario' => (float)($m->costo_unitario ?? 0),
                        'fuente' => 'kardex',
                    ];
                })
            );
        }

        // Costos
        if (in_array($this->fuenteDatos, ['costos', 'ambas'])) {
            $q = ProductoCostoMovimiento::query()
                ->with('tipoDocumento:id,codigo,nombre')
                ->where('producto_id', $this->producto_id);

            if ($this->bodega_id) $q->where('bodega_id', $this->bodega_id);
            if ($desde)           $q->where('fecha', '>=', $desde);
            if ($hasta)           $q->where('fecha', '<=', $hasta);

            if (trim($this->buscarDoc) !== '') {
                $txt = '%' . (string) Str::of($this->buscarDoc)->trim() . '%';
                $q->where(function ($x) use ($txt) {
                    $x->orWhere('doc_id', 'like', $txt)
                      ->orWhere('ref', 'like', $txt)
                      ->orWhereHas('tipoDocumento', fn($q2) =>
                          $q2->where('nombre','like',$txt)->orWhere('codigo','like',$txt)
                      );
                });
            }

            $movs = $movs->merge(
                $q->orderBy('fecha')->orderBy('id')->get()->map(function ($m) {
                    $cant = (float)($m->cantidad ?? 0);
                    return [
                        'id' => 'c_'.$m->id,
                        'model_id' => $m->id,
                        'fecha' => $m->fecha,
                        'bodega_id' => $m->bodega_id,
                        'tipo_documento_id' => $m->tipo_documento_id,
                        'doc_tipo' => optional($m->tipoDocumento)->codigo ?: optional($m->tipoDocumento)->nombre,
                        'doc_id' => $m->doc_id,
                        'ref' => $m->ref,
                        'tipo_documento' => $m->tipoDocumento,
                        'entrada' => $cant > 0 ? $cant : 0,
                        'salida'  => $cant < 0 ? abs($cant) : 0,
                        'cantidad'=> $cant,
                        'signo'   => $cant >= 0 ? 1 : -1,
                        'costo_unitario' => (float)($m->costo_unit_mov ?? 0),
                        'fuente' => 'costos',
                        'metodo_costeo'          => $m->metodo_costeo,
                        'costo_prom_anterior'    => $m->costo_prom_anterior,
                        'costo_prom_nuevo'       => $m->costo_prom_nuevo,
                        'ultimo_costo_anterior'  => $m->ultimo_costo_anterior,
                        'ultimo_costo_nuevo'     => $m->ultimo_costo_nuevo,
                        'tipo_evento'            => $m->tipo_evento,
                        'valor_mov'              => $m->valor_mov,
                    ];
                })
            );
        }

        // Orden estable
        $movs = $movs->sortBy([['fecha','asc'], ['model_id','asc']])->values();

        // ===== Saldo final GLOBAL del rango (para tarjetas) =====
        $finCant = $this->saldoInicialCant;
        $finVal  = $this->saldoInicialVal;
        foreach ($movs as $m) {
            $tipo = $m['entrada'] > 0 ? 'ENTRADA' : 'SALIDA';
            $c    = $tipo === 'ENTRADA' ? $m['entrada'] : $m['salida'];

            if ($tipo === 'ENTRADA') {
                $finVal  += $c * $m['costo_unitario'];
                $finCant += $c;
            } else {
                $cpu = $finCant > 0 ? ($finVal / max($finCant, 1e-9)) : 0.0;
                $finCant -= $c;
                $finVal  -= $c * $cpu;
                if ($finCant < 1e-9) { $finCant = 0.0; $finVal = 0.0; }
            }
        }
        $this->saldoFinalCant = round($finCant, 6);
        $this->saldoFinalVal  = round($finVal, 2);

        // ===== Paginación manual =====
        $total       = $movs->count();
        $currentPage = (int) ($this->page ?? \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1);
        $currentPage = max(1, $currentPage);
        $offset      = ($currentPage - 1) * $this->perPage;
        $itemsPagina = $movs->slice($offset, $this->perPage)->values();

        // Saldos corridos hasta el inicio de la página
        [$saldoCant, $saldoVal] = [$this->saldoInicialCant, $this->saldoInicialVal];
        if ($offset > 0) {
            foreach ($movs->slice(0, $offset) as $m) {
                $tipo = $m['entrada'] > 0 ? 'ENTRADA' : 'SALIDA';
                $c    = $tipo === 'ENTRADA' ? $m['entrada'] : $m['salida'];
                if ($tipo === 'ENTRADA') {
                    $saldoVal  += $c * $m['costo_unitario'];
                    $saldoCant += $c;
                } else {
                    $cpu = $saldoCant > 0 ? ($saldoVal / max($saldoCant, 1e-9)) : 0.0;
                    $saldoCant -= $c;
                    $saldoVal  -= $c * $cpu;
                    if ($saldoCant < 1e-9) { $saldoCant = 0.0; $saldoVal = 0.0; }
                }
            }
        }

        // Items de la página con saldos corridos y datos para agrupar
        $items = $itemsPagina->map(function ($m) use (&$saldoCant, &$saldoVal) {
            $tipo = $m['entrada'] > 0 ? 'ENTRADA' : 'SALIDA';
            $c    = $tipo === 'ENTRADA' ? $m['entrada'] : $m['salida'];

            $cpu = null;
            if ($tipo === 'ENTRADA') {
                $saldoVal  += $c * $m['costo_unitario'];
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
                'doc_tipo'   => (string)($m['doc_tipo'] ?? ''),
                'doc_id'     => (string)($m['doc_id'] ?? ''),
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

        return new LengthAwarePaginator(
            $items,
            $total,
            $this->perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}
