<?php

namespace App\Livewire\Finanzas;

use App\Models\Factura\Factura;
use Livewire\Component;
use Livewire\WithPagination;

class InformeVentas extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filtros
    public string $estadoFiltro    = 'todos';  // borrador, emitida, parcialmente_pagada, pagada, anulada, vencida
    public string $tipoPagoFiltro  = 'todos';  // contado / credito / transferencia
    public string $filtroCliente   = '';
    public ?string $fechaInicio    = null;
    public ?string $fechaFin       = null;
    public string $empresaFiltro   = 'todas';

    // KPIs
    public int $totalFacturas      = 0;
    public float $totalContado     = 0;
    public float $totalCredito     = 0;
    public float $totalFacturado   = 0;
    public float $totalPagado      = 0;
    public float $totalSaldo       = 0;

    public function mount(): void
    {
        // Rango por defecto: mes actual
        $this->fechaInicio = now()->startOfMonth()->toDateString();
        $this->fechaFin    = now()->toDateString();
    }

    // Cada vez que cambie algo, resetea la página
    public function updated($property): void
    {
        $this->resetPage();
    }

    public function cargarVentas(): void
    {
        // Botón Buscar: simplemente resetear página
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->estadoFiltro   = 'todos';
        $this->tipoPagoFiltro = 'todos';
        $this->empresaFiltro  = 'todas';
        $this->filtroCliente  = '';
        $this->fechaInicio    = now()->startOfMonth()->toDateString();
        $this->fechaFin       = now()->toDateString();

        $this->resetPage();
    }

    protected function baseQuery()
    {
        $q = Factura::query()
            ->with(['cliente', 'empresa']);

        // Estado
        if ($this->estadoFiltro !== 'todos') {
            if ($this->estadoFiltro === 'vencida') {
                // Vencida = saldo > 0 y vencimiento < hoy
                $q->where('saldo', '>', 0)
                  ->whereNotNull('vencimiento')
                  ->whereDate('vencimiento', '<', now()->toDateString());
            } else {
                $q->where('estado', $this->estadoFiltro);
            }
        }

        // Tipo pago
        if ($this->tipoPagoFiltro !== 'todos') {
            $q->where('tipo_pago', $this->tipoPagoFiltro);
        }

        // Empresa
        if ($this->empresaFiltro !== 'todas' && $this->empresaFiltro !== '') {
            $q->where('empresa_id', $this->empresaFiltro);
        }

        // Cliente
        if ($this->filtroCliente !== '') {
            $f = $this->filtroCliente;
            $q->whereHas('cliente', function ($qq) use ($f) {
                $qq->where('razon_social', 'like', "%{$f}%");
            });
        }

        // Rango de fechas
        if ($this->fechaInicio && $this->fechaFin) {
            $q->whereBetween('fecha', [$this->fechaInicio, $this->fechaFin]);
        } elseif ($this->fechaInicio) {
            $q->whereDate('fecha', '>=', $this->fechaInicio);
        } elseif ($this->fechaFin) {
            $q->whereDate('fecha', '<=', $this->fechaFin);
        }

        return $q;
    }

    protected function calcularKpis($query): void
    {
        $base = clone $query;

        $this->totalFacturas    = (int) $base->count();
        $this->totalFacturado   = (float) $base->sum('total');
        $this->totalPagado      = (float) $base->sum('pagado');
        $this->totalSaldo       = (float) $base->sum('saldo');

        $this->totalContado     = (float) (clone $query)
            ->where('tipo_pago', 'contado')
            ->sum('total');

        $this->totalCredito     = (float) (clone $query)
            ->where('tipo_pago', 'credito')
            ->sum('total');
    }

    public function render()
    {
        $query    = $this->baseQuery();
        $facturas = $query->orderByDesc('fecha')->orderByDesc('id')->paginate(15);

        $this->calcularKpis($query);

        return view('livewire.finanzas.informe-ventas', [
            'facturas' => $facturas,
        ]);
    }
}
