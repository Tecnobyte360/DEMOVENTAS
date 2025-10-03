<?php

namespace App\Livewire\Contabilidad;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Asiento\Asiento;
use App\Models\Movimiento\Movimiento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Masmerise\Toaster\PendingToast;

class Asientos extends Component
{
    use WithPagination;

    public string $search = '';
    public ?string $desde = null;
    public ?string $hasta = null;
    public ?string $origen = null;
    public ?string $tercero = null;
    public int $perPage = 15;

    // Modal
    public bool $showModal = false;
    public ?array $asientoDetalle = null;
    public array $movimientos = [];
    public float $modalTotalDebito = 0.0;
    public float $modalTotalCredito = 0.0;
    public float $modalTotalBase = 0.0;
    public float $modalTotalImpuesto = 0.0;

    protected $queryString = [
        'search'  => ['except' => ''],
        'desde'   => ['except' => null],
        'hasta'   => ['except' => null],
        'origen'  => ['except' => null],
        'tercero' => ['except' => null],
        'page'    => ['except' => 1],
    ];

    public function updatingSearch()   { $this->resetPage(); }
    public function updatingDesde()    { $this->resetPage(); }
    public function updatingHasta()    { $this->resetPage(); }
    public function updatingOrigen()   { $this->resetPage(); }
    public function updatingTercero()  { $this->resetPage(); }
    public function updatingPerPage()  { $this->resetPage(); }

    public function render()
    {
        try {
            $asientos = Asiento::query()
                ->with(['tercero:id,razon_social,nit'])
                ->when($this->search !== '', function ($q) {
                    $s = "%{$this->search}%";
                    $q->where(function ($q) use ($s) {
                        $q->where('glosa', 'like', $s)
                          ->orWhere('id', intval($this->search))
                          ->orWhere('origen', 'like', $s)
                          ->orWhere('origen_id', 'like', $s);
                    });
                })
                ->when($this->desde, fn($q) => $q->whereDate('fecha', '>=', $this->desde))
                ->when($this->hasta, fn($q) => $q->whereDate('fecha', '<=', $this->hasta))
                ->when($this->origen, fn($q) => $q->where('origen', $this->origen))
                ->when($this->tercero, function ($q) {
                    $s = "%{$this->tercero}%";
                    $q->whereHas('tercero', function ($qt) use ($s) {
                        $qt->where('razon_social', 'like', $s)
                           ->orWhere('nit', 'like', $s);
                    });
                })
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->paginate($this->perPage);

            return view('livewire.contabilidad.asientos', compact('asientos'));
        } catch (\Throwable $e) {
            Log::error('ASIENTOS RENDER ERROR', ['msg' => $e->getMessage()]);
            PendingToast::create()->error()->message('No se pudo cargar la lista de asientos.')->duration(6000);
            return view('livewire.contabilidad.asientos', ['asientos' => collect()]);
        }
    }

    public function ver(int $id): void
    {
        try {
            $a = Asiento::with(['tercero:id,razon_social,nit'])->findOrFail($id);

            $rows = Movimiento::query()
                ->join('plan_cuentas as pc', 'pc.id', '=', 'movimientos.cuenta_id')
                ->leftJoin('socio_negocios as sn', 'sn.id', '=', 'movimientos.tercero_id')
                // 🔴 clave: tomar el impuesto elegido en el movimiento
                ->leftJoin('impuestos as imp_m', 'imp_m.id', '=', 'movimientos.impuesto_id')
                ->where('movimientos.asiento_id', $id)
                ->orderBy('pc.codigo')
                ->get([
                    'movimientos.id as mov_id',
                    'movimientos.cuenta_id',
                    'pc.codigo as cuenta_codigo',
                    'pc.nombre as cuenta_nombre',
                    'movimientos.debito',
                    'movimientos.credito',
                    'movimientos.base_gravable',
                    'movimientos.tarifa_pct',
                    'movimientos.valor_impuesto',
                    'movimientos.detalle',
                    'movimientos.descripcion',
                    'sn.razon_social as tercero_nombre',
                    'sn.nit as tercero_nit',
                    // 👇 código y nombre del impuesto
                    'imp_m.codigo as impuesto_codigo',
                    'imp_m.nombre as impuesto_nombre',
                ]);

            $this->movimientos = [];
            $this->modalTotalDebito = $this->modalTotalCredito = $this->modalTotalBase = $this->modalTotalImpuesto = 0.0;

            foreach ($rows as $m) {
                $deb  = (float)($m->debito ?? 0);
                $cre  = (float)($m->credito ?? 0);
                $base = (float)($m->base_gravable ?? 0);
                $impV = (float)($m->valor_impuesto ?? 0);

                $this->movimientos[] = [
                    'mov_id'          => (int)$m->mov_id,
                    'cuenta_id'       => (int)$m->cuenta_id,
                    'codigo'          => (string)$m->cuenta_codigo,
                    'nombre'          => (string)$m->cuenta_nombre,
                    'debito'          => $deb,
                    'credito'         => $cre,
                    'base_gravable'   => $base,
                    'tarifa_pct'      => $m->tarifa_pct !== null ? (float)$m->tarifa_pct : null,
                    'valor_impuesto'  => $impV,
                    'impuesto_codigo' => $m->impuesto_codigo,
                    'impuesto_nombre' => $m->impuesto_nombre,
                    'tercero_nombre'  => $m->tercero_nombre,
                    'tercero_nit'     => $m->tercero_nit,
                    'detalle'         => $m->detalle ?? $m->descripcion ?? '',
                ];

                $this->modalTotalDebito   += $deb;
                $this->modalTotalCredito  += $cre;
                $this->modalTotalBase     += $base;
                $this->modalTotalImpuesto += $impV;
            }

            $this->asientoDetalle = [
                'id'         => $a->id,
                'fecha'      => $a->fecha,
                'glosa'      => $a->glosa,
                'origen'     => $a->origen,
                'origen_id'  => $a->origen_id,
                'moneda'     => $a->moneda,
                'total_debe' => $a->total_debe,
                'total_haber'=> $a->total_haber,
                'tercero'    => $a->tercero ? [
                    'razon_social' => $a->tercero->razon_social,
                    'nit'          => $a->tercero->nit,
                ] : null,
            ];

            $this->showModal = true;
        } catch (\Throwable $e) {
            Log::error('ASIENTOS VER ERROR', ['id' => $id, 'msg' => $e->getMessage()]);
            PendingToast::create()->error()->message('No se pudo cargar el detalle del asiento.')->duration(6000);
        }
    }

    public function cerrarModal(): void
    {
        $this->showModal = false;
        $this->asientoDetalle = null;
        $this->movimientos = [];
        $this->modalTotalDebito = 0.0;
        $this->modalTotalCredito = 0.0;
        $this->modalTotalBase = 0.0;
        $this->modalTotalImpuesto = 0.0;
    }

    public function abrirOrigen(int $asientoId): void
{
    $a = \App\Models\Asiento\Asiento::select('id','origen','origen_id')->findOrFail($asientoId);

    if ($a->origen === 'factura' && $a->origen_id) {
        // Livewire 3:
        $this->redirectRoute('facturas.edit', ['id' => $a->origen_id], navigate: true);

        // Livewire 2 (alternativa):
        // redirect()->route('facturas.edit', $a->origen_id)->send();
        return;
    }

    // Si no es factura, abre tu modal de detalle
    $this->ver($asientoId);
}
}
