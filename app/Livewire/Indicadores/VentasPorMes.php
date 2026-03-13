<?php

namespace App\Livewire\Indicadores;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class VentasPorMes extends Component
{
    public array $labels = [];

    public array $dataContado = [];
    public array $dataCredito = [];
    public array $dataNotasCredito = []; // abs() para mostrar
    public array $dataNeto = [];

    public float $totalAnioContado = 0;
    public float $totalAnioCredito = 0;
    public float $totalAnioNotasCredito = 0; // positivo para KPI
    public float $totalAnioNeto = 0;

    public function mount(): void
    {
        $this->cargar();
    }

protected function cargar(): void
{
    $year = now()->year;

    $rows = DB::table('facturas as f')
        ->join('series as s', 's.id', '=', 'f.serie_id')
        ->selectRaw('MONTH(f.fecha) as mes')

        ->selectRaw("
            SUM(
                CASE
                    WHEN s.prefijo = 'FRM'
                     AND f.total > 0
                     AND f.tipo_pago = 'contado'
                     AND f.numero IS NOT NULL
                     AND f.numero <> ''
                     AND f.estado <> 'anulada'
                    THEN f.total
                    ELSE 0
                END
            ) as contado
        ")

        ->selectRaw("
            SUM(
                CASE
                    WHEN s.prefijo = 'FRM'
                     AND f.total > 0
                     AND f.tipo_pago = 'credito'
                     AND f.numero IS NOT NULL
                     AND f.numero <> ''
                     AND f.estado <> 'anulada'
                    THEN f.total
                    ELSE 0
                END
            ) as credito
        ")

        ->selectRaw("
            SUM(
                CASE
                    WHEN s.prefijo = 'NCV'
                     AND f.numero IS NOT NULL
                     AND f.numero <> ''
                     AND f.estado <> 'anulada'
                    THEN ABS(f.total)
                    ELSE 0
                END
            ) as notas_credito
        ")

        ->selectRaw("
            SUM(
                CASE
                    WHEN s.prefijo = 'FRM'
                     AND f.numero IS NOT NULL
                     AND f.numero <> ''
                     AND f.estado <> 'anulada'
                    THEN f.total
                    WHEN s.prefijo = 'NCV'
                     AND f.numero IS NOT NULL
                     AND f.numero <> ''
                     AND f.estado <> 'anulada'
                    THEN -ABS(f.total)
                    ELSE 0
                END
            ) as neto
        ")

        ->whereYear('f.fecha', $year)
        ->whereIn('s.prefijo', ['FRM', 'NCV'])
        ->groupByRaw('MONTH(f.fecha)')
        ->orderByRaw('MONTH(f.fecha)')
        ->get()
        ->keyBy('mes');

    $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    $this->labels = $meses;
    $this->dataContado = [];
    $this->dataCredito = [];
    $this->dataNotasCredito = [];
    $this->dataNeto = [];

    $this->totalAnioContado = 0;
    $this->totalAnioCredito = 0;
    $this->totalAnioNotasCredito = 0;
    $this->totalAnioNeto = 0;

    for ($m = 1; $m <= 12; $m++) {
        $r = $rows->get($m);

        $contado = (float) ($r->contado ?? 0);
        $credito = (float) ($r->credito ?? 0);
        $notas   = (float) ($r->notas_credito ?? 0);
        $neto    = (float) ($r->neto ?? 0);

        $this->dataContado[] = $contado;
        $this->dataCredito[] = $credito;
        $this->dataNotasCredito[] = $notas;
        $this->dataNeto[] = $neto;

        $this->totalAnioContado += $contado;
        $this->totalAnioCredito += $credito;
        $this->totalAnioNotasCredito += $notas;
        $this->totalAnioNeto += $neto;
    }
}
   

    public function render()
    {
        return view('livewire.indicadores.ventas-por-mes');
    }
}
