<?php

namespace App\Livewire\Reportes;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class InformeVentas extends Component
{
    use WithPagination;

    // Filtros
    public ?string $desde = null;
    public ?string $hasta = null;
    public ?int $bodega_id = null;
    public ?int $user_id = null;           // vendedor / cajero
    public ?int $medio_pago_id = null;     // opcional
    public ?string $estado = null;         // 'pagada', 'anulada', etc (según tu sistema)
    public string $search = '';

    // Control
    public bool $filtroAplicado = false;
    protected string $paginationTheme = 'tailwind';

    // Catálogos (ajusta si tus modelos son otros)
    public array $bodegas = [];
    public array $usuarios = [];
    public array $mediosPago = [];

    public function mount()
    {
        // Defaults: hoy
        $this->desde = now()->format('Y-m-d');
        $this->hasta = now()->format('Y-m-d');

        // Carga catálogos (AJUSTA tablas si difieren)
        $this->bodegas = DB::table('bodegas')->select('id', 'nombre')->orderBy('nombre')->get()->toArray();
        $this->usuarios = DB::table('users')->select('id', 'name')->orderBy('name')->get()->toArray();
        $this->mediosPago = DB::table('medio_pagos')->select('id', 'nombre')->orderBy('nombre')->get()->toArray(); // <- AJUSTA si es otra tabla
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function buscar()
    {
        $this->validate([
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date', 'after_or_equal:desde'],
        ]);

        $this->filtroAplicado = true;
        $this->resetPage();
    }

    public function limpiar()
    {
        $this->reset(['bodega_id', 'user_id', 'medio_pago_id', 'estado', 'search']);
        $this->desde = now()->format('Y-m-d');
        $this->hasta = now()->format('Y-m-d');
        $this->filtroAplicado = false;
        $this->resetPage();
    }

    /**
     * Query base de facturas (AJUSTA AQUÍ si tu tabla/modelo es otro)
     *
     * Estructura esperada:
     * - facturas.id
     * - facturas.numero (o doc_num)
     * - facturas.fecha (date/datetime)
     * - facturas.cliente_nombre (o socio_nombre)
     * - facturas.total
     * - facturas.estado
     * - facturas.user_id
     * - facturas.bodega_id
     */
    private function queryVentas()
    {
        $q = DB::table('facturas as f') // <- AJUSTA si tu tabla no es 'facturas'
            ->select([
                'f.id',
                'f.numero',
                'f.fecha',
                'f.cliente_nombre',
                'f.total',
                'f.estado',
                'f.user_id',
                'f.bodega_id',
            ])
            ->whereBetween(DB::raw('date(f.fecha)'), [$this->desde, $this->hasta]);

        if ($this->bodega_id) $q->where('f.bodega_id', $this->bodega_id);
        if ($this->user_id)   $q->where('f.user_id', $this->user_id);
        if ($this->estado)    $q->where('f.estado', $this->estado);

        if ($this->search !== '') {
            $s = trim($this->search);
            $q->where(function ($w) use ($s) {
                $w->where('f.numero', 'like', "%{$s}%")
                  ->orWhere('f.cliente_nombre', 'like', "%{$s}%");
            });
        }

        // Filtro por medio de pago (por join con pagos)
        if ($this->medio_pago_id) {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('factura_pagos as fp') // <- AJUSTA si tu tabla es otra
                    ->whereColumn('fp.factura_id', 'f.id')
                    ->where('fp.medio_pago_id', $this->medio_pago_id);
            });
        }

        return $q;
    }

    private function kpis()
    {
        if (!$this->filtroAplicado) {
            return [
                'docs' => 0,
                'ventas' => 0,
                'devoluciones' => 0,
                'neto' => 0,
            ];
        }

        $base = $this->queryVentas();

        $docs = (clone $base)->count();

        // Asumimos: total positivo = venta, total negativo = devolución (AJUSTA si manejas devoluciones en otra tabla)
        $ventas = (clone $base)->where('f.total', '>', 0)->sum('f.total');
        $devoluciones = (clone $base)->where('f.total', '<', 0)->sum('f.total'); // será negativo

        $neto = $ventas + $devoluciones;

        return compact('docs', 'ventas', 'devoluciones', 'neto');
    }

    public function exportarCsv()
    {
        if (!$this->filtroAplicado) return;

        $rows = $this->queryVentas()
            ->orderBy('f.fecha', 'desc')
            ->get();

        $filename = "informe_ventas_{$this->desde}_a_{$this->hasta}.csv";

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Fecha', 'Documento', 'Cliente', 'Total', 'Estado']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    Carbon::parse($r->fecha)->format('Y-m-d H:i'),
                    $r->numero,
                    $r->cliente_nombre,
                    $r->total,
                    $r->estado,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render()
    {
        $kpis = $this->kpis();

        $ventas = collect();
        $paginado = null;

        if ($this->filtroAplicado) {
            $paginado = $this->queryVentas()
                ->orderBy('f.fecha', 'desc')
                ->paginate(15);

            $ventas = $paginado->items();
        }

        return view('livewire.reportes.informe-ventas', [
            'kpis' => $kpis,
            'paginado' => $paginado,
            'ventas' => $ventas,
        ]);
    }
}
