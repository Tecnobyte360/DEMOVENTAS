<?php

namespace App\Livewire\Facturas;

use App\Models\Factura\FacturaPago;
use App\Models\MediosPago\MedioPagos;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class Listapagosrecibidos extends Component
{
    use WithPagination;

    /** -----------------------------
     *  PROPIEDADES
     * ----------------------------- */
    public string $buscar = '';
    public ?string $fecha_inicio = null;
    public ?string $fecha_fin = null;
    public ?int $medio_pago_id = null;
    public ?string $metodo = null;
    public ?float $monto_min = null;
    public ?float $monto_max = null;

    // Orden / paginación
    public string $ordenarPor = 'fecha';
    public string $direccion = 'desc';
    public int $porPagina = 15;

    // Totales
    public float $totalGeneral = 0.0;

    // Catálogo de medios de pago
    public array $mediosPago = [];

    /** -----------------------------
     *  QUERY STRING
     * ----------------------------- */
    protected $queryString = [
        'buscar'        => ['except' => ''],
        'fecha_inicio'  => ['except' => null],
        'fecha_fin'     => ['except' => null],
        'medio_pago_id' => ['except' => null],
        'metodo'        => ['except' => null],
        'monto_min'     => ['except' => null],
        'monto_max'     => ['except' => null],
        'ordenarPor'    => ['except' => 'fecha'],
        'direccion'     => ['except' => 'desc'],
        'porPagina'     => ['except' => 15],
    ];

    protected $listeners = ['refrescar' => '$refresh'];

    /** -----------------------------
     *  CICLO DE VIDA
     * ----------------------------- */
    public function mount(): void
    {
        // Rango por defecto: mes actual
        $this->fecha_inicio = now()->startOfMonth()->toDateString();
        $this->fecha_fin    = now()->toDateString();

        // Catálogo de medios de pago
        $this->mediosPago = MedioPagos::query()
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo'])
            ->toArray();
    }

    /** -----------------------------
     *  ACTUALIZADORES AUTOMÁTICOS
     * ----------------------------- */
    public function updatingBuscar()        { $this->resetPage(); }
    public function updatingFechaInicio()   { $this->resetPage(); }
    public function updatingFechaFin()      { $this->resetPage(); }
    public function updatingMedioPagoId()   { $this->resetPage(); }
    public function updatingMetodo()        { $this->resetPage(); }
    public function updatingMontoMin()      { $this->resetPage(); }
    public function updatingMontoMax()      { $this->resetPage(); }
    public function updatingPorPagina()     { $this->resetPage(); }

    /** -----------------------------
     *  ORDENAR COLUMNAS
     * ----------------------------- */
    public function ordenarPor(string $campo): void
    {
        if ($this->ordenarPor === $campo) {
            $this->direccion = $this->direccion === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenarPor = $campo;
            $this->direccion  = 'asc';
        }
        $this->resetPage();
    }

    /** -----------------------------
     *  CONSULTA BASE
     * ----------------------------- */
   protected function baseQuery(): Builder
{
    $q = FacturaPago::query()
        ->with([
            'factura:id,numero,prefijo,serie_id,socio_negocio_id,fecha,saldo,estado',
            'factura.serie:id,prefijo,longitud',
            'factura.cliente:id,razon_social,nit',
            'medioPago:id,nombre,codigo',
        ])
        ->leftJoin('facturas', 'factura_pagos.factura_id', '=', 'facturas.id')
        ->leftJoin('socio_negocios', 'facturas.socio_negocio_id', '=', 'socio_negocios.id')
        ->select('factura_pagos.*');

    // ✅ SOLO pagos de facturas que aún tienen saldo pendiente
    $q->where('facturas.saldo', '>', 0);

    // (Opcional) excluir facturas anuladas/canceladas
    // $q->whereNotIn('facturas.estado', ['ANULADA', 'CANCELADA']);

    // Filtro de fechas
    if ($this->fecha_inicio) {
        $q->whereDate('factura_pagos.fecha', '>=', $this->fecha_inicio);
    }
    if ($this->fecha_fin) {
        $q->whereDate('factura_pagos.fecha', '<=', $this->fecha_fin);
    }

    // Filtros adicionales
    if ($this->medio_pago_id) {
        $q->where('factura_pagos.medio_pago_id', $this->medio_pago_id);
    }
    if (!empty($this->metodo)) {
        $q->where('factura_pagos.metodo', 'like', '%' . $this->metodo . '%');
    }
    if ($this->monto_min !== null && $this->monto_min !== '') {
        $q->where('factura_pagos.monto', '>=', (float) $this->monto_min);
    }
    if ($this->monto_max !== null && $this->monto_max !== '') {
        $q->where('factura_pagos.monto', '<=', (float) $this->monto_max);
    }

    // Búsqueda general
    if ($this->buscar !== '') {
        $busca = '%' . trim($this->buscar) . '%';
        $q->where(function ($w) use ($busca) {
            $w->where('factura_pagos.referencia', 'like', $busca)
                ->orWhere('socio_negocios.razon_social', 'like', $busca)
                ->orWhere('socio_negocios.numero_documento', 'like', $busca)
                ->orWhere('facturas.numero', 'like', $busca)
                ->orWhere('factura_pagos.metodo', 'like', $busca);
        });
    }

    // Orden
    $permitidos = ['fecha', 'monto', 'metodo', 'referencia'];
    if (!in_array($this->ordenarPor, $permitidos, true)) {
        $this->ordenarPor = 'fecha';
    }
    $dir = $this->direccion === 'asc' ? 'asc' : 'desc';
    $q->orderBy('factura_pagos.' . $this->ordenarPor, $dir)
      ->orderBy('factura_pagos.id', 'desc');

    return $q;
}


    /** -----------------------------
     *  RENDERIZADO
     * ----------------------------- */
    public function render()
    {
        $query = $this->baseQuery();

        // Total general (sin paginar)
        $this->totalGeneral = (float) (clone $query)->sum('factura_pagos.monto');

        // Paginación
     $pagos = $query->paginate($this->porPagina);
$totalPagina = (float) collect($pagos->items())->sum('monto');
        return view('livewire.facturas.listapagosrecibidos', [
            'pagos'        => $pagos,
            'totalPagina'  => $totalPagina,
            'totalGeneral' => $this->totalGeneral,
        ]);
    }
}
