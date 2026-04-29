<?php

namespace App\Livewire\Indicadores;

use Livewire\Component;
use App\Models\Factura\Factura;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Indicadores extends Component
{
    public float $totalFacturado = 0;
    public float $totalPagado = 0;
    public float $totalPendiente = 0;
    public int   $totalPedidos = 0;

    // ✅ Datos para el chart (solo HOY)
    public array $chartLabels = [];
    public array $chartFacturado = [];
    public array $chartPagado = [];
    public array $chartPendiente = [];

    protected array $codigosVentas = ['FACTURA', 'NOTA_CREDITO'];

    public function mount(): void
    {
        $hoy = Carbon::today()->toDateString();
        $codigos = array_map('strtoupper', $this->codigosVentas);

        // Base: solo facturas reales (no borradores, no anuladas)
        $base = Factura::query()
            ->whereDate('fecha', $hoy)
            ->whereNotIn('estado', ['borrador', 'anulada'])
            ->whereHas('serie.tipo', function ($t) use ($codigos) {
                $t->whereIn(DB::raw('UPPER(codigo)'), $codigos);
            });

        // Ventas = solo lo PAGADO (no contar facturas pendientes como ventas)
        $qPagadas = (clone $base)->where('estado', 'pagada');
        $this->totalPedidos   = (int) $qPagadas->count();
        $this->totalFacturado = (float) $qPagadas->sum('total');
        $this->totalPagado    = (float) $qPagadas->sum('pagado');

        // Saldo pendiente = solo facturas emitidas o parcialmente pagadas (no borradores)
        $qPendientes = (clone $base)->whereIn('estado', ['emitida', 'parcialmente_pagada']);
        $this->totalPendiente = (float) $qPendientes->sum('saldo');

        // ✅ Chart: una sola etiqueta (HOY)
        $this->chartLabels    = [Carbon::today()->translatedFormat('d M')];
        $this->chartFacturado = [$this->totalFacturado];
        $this->chartPagado    = [$this->totalPagado];
        $this->chartPendiente = [$this->totalPendiente];
    }

    public function render()
    {
        return view('livewire.indicadores.indicadores');
    }
}
