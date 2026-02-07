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

        $q = Factura::query()
            ->whereDate('fecha', $hoy)
            ->whereHas('serie.tipo', function ($t) use ($codigos) {
                $t->whereIn(DB::raw('UPPER(codigo)'), $codigos);
            });

        $this->totalPedidos   = (int) $q->count();
        $this->totalFacturado = (float) $q->sum('total');
        $this->totalPagado    = (float) $q->sum('pagado');
        $this->totalPendiente = (float) $q->sum('saldo');

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
