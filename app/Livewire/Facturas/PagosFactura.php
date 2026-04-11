<?php

namespace App\Livewire\Facturas;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Masmerise\Toaster\PendingToast;

use App\Models\Factura\Factura;
use App\Models\MediosPago\MedioPagos;
use App\Services\ContabilidadService;
use App\Models\TurnosCaja\turnos_caja;
use App\Models\TurnosCaja\CajaMovimiento;
use App\Models\Serie\Serie;

class PagosFactura extends Component
{
    private const TIPO_FACTURA_VENTA  = 1;
    private const TIPO_FACTURA_COMPRA = 5;

    public ?int $facturaId = null;

    public bool $show = false;
    public string $fecha = '';
    public ?string $notas = null;

    /** venta | compra */
    public string $modoDocumento = 'venta';
    public int $facturaSelectRefresh = 0;

    /** @var Collection<int, MedioPagos> */
    public Collection $medios;

    public float $fac_total  = 0.0;
    public float $fac_pagado = 0.0;
    public float $fac_saldo  = 0.0;

    public array $items = [];

    public float $sumPct   = 0.0;
    public float $sumMonto = 0.0;
    public float $diff     = 0.0;

    public string $buscarFactura = '';
    public ?int $serieId = null;
    public array $seriesDisponibles = [];

    protected $rules = [
        'fecha'                 => 'required|date',
        'items'                 => 'required|array|min:1|max:1',
        'items.*.medio_pago_id' => 'required|integer',
        'items.*.monto'         => 'required|numeric|min:0.01',
        'items.*.referencia'    => 'nullable|string|max:120',
        'notas'                 => 'nullable|string',
        'facturaId'             => 'nullable|integer',
        'modoDocumento'         => 'required|in:venta,compra',
        'serieId'               => 'nullable|integer',
    ];

    public function mount(): void
    {
        $this->fecha = now()->toDateString();
        $this->medios = collect();

        $this->items = [[
            'medio_pago_id' => null,
            'monto'         => 0,
            'referencia'    => null,
        ]];

        $this->modoDocumento = 'venta';
        $this->cargarSeriesDisponibles();
        $this->serieId = $this->resolverSerieInicial();

        $this->recalc();
    }

    public function render()
    {
        $this->cargarMedios();
        $this->cargarSeriesDisponibles();

        if ($this->serieId && !in_array($this->serieId, $this->idsSeriesPermitidas(), true)) {
            $this->serieId = $this->resolverSerieInicial();
        }

        $facturasPendientes = $this->queryFacturasPendientes();

        $this->recalc();

        $serieNombre = '—';
        foreach ($this->seriesDisponibles as $tipoNombre => $series) {
            foreach ($series as $s) {
                if ((int) $s['id'] === (int) $this->serieId) {
                    $serieNombre = trim(($s['prefijo'] ?? '') . ' — ' . ($s['nombre'] ?? ''));
                    break 2;
                }
            }
        }

        return view('livewire.facturas.pagos-factura', [
            'medios'             => $this->medios,
            'facturasPendientes' => $facturasPendientes,
            'serieNombre'        => $serieNombre,
            'modoDocumento'      => $this->modoDocumento,
        ]);
    }

    #[On('abrir-modal-pago')]
    public function abrir(?int $facturaId = null, string $modoDocumento = 'venta'): void
    {
        $this->show = true;
        $this->fecha = now()->toDateString();
        $this->notas = null;
        $this->buscarFactura = '';
        $this->facturaId = null;

        $this->modoDocumento = in_array($modoDocumento, ['venta', 'compra'], true)
            ? $modoDocumento
            : 'venta';

        $this->cargarSeriesDisponibles();
        $this->serieId = $this->resolverSerieInicial();

        $this->cargarMedios();

        if ($facturaId) {
            $factura = Factura::with('serie.tipo')->find($facturaId);

            if ($factura) {
                $tipoSerieId = (int) ($factura->serie?->tipo_documento_id ?? 0);

                if (
                    ($this->modoDocumento === 'venta' && $tipoSerieId === self::TIPO_FACTURA_VENTA) ||
                    ($this->modoDocumento === 'compra' && $tipoSerieId === self::TIPO_FACTURA_COMPRA)
                ) {
                    $this->facturaId = $facturaId;
                    $this->serieId = (int) $factura->serie_id;
                    $this->cargarFacturaSeleccionada($facturaId, true);
                } else {
                    $this->resetFacturaYItems();
                }
            } else {
                $this->resetFacturaYItems();
            }
        } else {
            $this->resetFacturaYItems();
        }

        $this->resetErrorBag();
        $this->resetValidation();
        $this->recalc();

        $this->dispatch('refresh-factura-select');
    }

    public function cerrar(): void
    {
        $this->show = false;
    }

    public function updatedModoDocumento(): void
    {
        $this->facturaId = null;
        $this->buscarFactura = '';

        $this->cargarSeriesDisponibles();
        $this->serieId = $this->resolverSerieInicial();

        $this->resetFacturaYItems();

        $this->facturaSelectRefresh++;
        $this->dispatch('refresh-factura-select');
    }

    public function updatedSerieId(): void
    {
        if ($this->serieId && !in_array((int) $this->serieId, $this->idsSeriesPermitidas(), true)) {
            $this->serieId = $this->resolverSerieInicial();
        }

        $this->facturaId = null;
        $this->buscarFactura = '';
        $this->resetFacturaYItems();

        $this->facturaSelectRefresh++;
        $this->dispatch('refresh-factura-select');
    }

    public function updatedBuscarFactura(): void
    {
        $this->facturaId = null;
        $this->resetFacturaYItems();

        $this->facturaSelectRefresh++;
        $this->dispatch('refresh-factura-select');
    }

   

    
  public function setFacturaId(?int $facturaId = null): void
{
    logger()->info('PagosFactura@setFacturaId - inicio', [
        'facturaId_recibido' => $facturaId,
        'modoDocumento'      => $this->modoDocumento,
        'serieId_actual'     => $this->serieId,
    ]);

    $this->facturaId = $facturaId ? (int) $facturaId : null;

    if (!$this->facturaId) {
        logger()->warning('PagosFactura@setFacturaId - facturaId vacío o nulo');
        $this->resetFacturaYItems();
        return;
    }

    $factura = Factura::with('serie.tipo')->find($this->facturaId);

    if (!$factura) {
        logger()->warning('PagosFactura@setFacturaId - factura no encontrada', [
            'facturaId' => $this->facturaId,
        ]);

        $this->facturaId = null;
        $this->resetFacturaYItems();
        return;
    }

    $tipoSerieId = (int) ($factura->serie?->tipo_documento_id ?? 0);
    $prefijo     = (string) ($factura->prefijo ?? '');
    $tipoPago    = mb_strtolower(trim((string) ($factura->tipo_pago ?? '')));

    $esCompraValida = $this->modoDocumento === 'compra'
        && (
            $tipoSerieId === self::TIPO_FACTURA_COMPRA
            || $prefijo === 'FAC-C'
        );

    $esVentaValida = $this->modoDocumento === 'venta'
        && (
            $tipoSerieId === self::TIPO_FACTURA_VENTA
            || $prefijo === 'FAC-V'
        )
        && in_array($tipoPago, ['credito', 'crédito'], true);

    logger()->info('PagosFactura@setFacturaId - validación factura', [
        'facturaId'       => $this->facturaId,
        'tipoSerieId'     => $tipoSerieId,
        'prefijo'         => $prefijo,
        'tipoPago'        => $tipoPago,
        'esCompraValida'  => $esCompraValida,
        'esVentaValida'   => $esVentaValida,
    ]);

    if (!$esCompraValida && !$esVentaValida) {
        PendingToast::create()->warning()
            ->message('La factura seleccionada no corresponde al tipo de documento actual.')
            ->duration(6000);

        logger()->warning('PagosFactura@setFacturaId - factura inválida para el modo actual', [
            'facturaId'   => $this->facturaId,
            'modoDocumento' => $this->modoDocumento,
            'tipoSerieId' => $tipoSerieId,
            'prefijo'     => $prefijo,
            'tipoPago'    => $tipoPago,
        ]);

        $this->facturaId = null;
        $this->resetFacturaYItems();
        return;
    }

    $this->serieId = (int) $factura->serie_id;

    $this->cargarMedios();
    $this->cargarFacturaSeleccionada($this->facturaId, true);

    $this->facturaSelectRefresh++;

    logger()->info('PagosFactura@setFacturaId - factura cargada correctamente', [
        'facturaId'            => $this->facturaId,
        'serieId'              => $this->serieId,
        'fac_total'            => $this->fac_total,
        'fac_pagado'           => $this->fac_pagado,
        'fac_saldo'            => $this->fac_saldo,
        'facturaSelectRefresh' => $this->facturaSelectRefresh,
    ]);
}
public function cambiarFactura(): void
{
    $this->facturaId = null;
    $this->buscarFactura = '';
    $this->resetFacturaYItems();
    $this->facturaSelectRefresh++;
    $this->dispatch('refresh-factura-select');
}


    private function cargarSeriesDisponibles(): void
    {
        $tipoDocumentoId = $this->tipoDocumentoIdActual();

        $this->seriesDisponibles = Serie::query()
            ->activa()
            ->with('tipo')
            ->where('documento', 'factura')
            ->where('tipo_documento_id', $tipoDocumentoId)
            ->orderBy('nombre')
            ->get()
            ->groupBy(fn($s) => $s->tipo?->nombre ?? 'Sin tipo')
            ->map(fn($grupo) => $grupo->map(fn($s) => [
                'id'      => (int) $s->id,
                'nombre'  => $s->nombre,
                'prefijo' => $s->prefijo,
            ])->values()->toArray())
            ->toArray();
    }

    private function resolverSerieInicial(): ?int
    {
        $tipoDocumentoId = $this->tipoDocumentoIdActual();

        $serie = Serie::query()
            ->activa()
            ->where('documento', 'factura')
            ->where('tipo_documento_id', $tipoDocumentoId)
            ->orderByDesc('es_default')
            ->orderBy('id')
            ->first();

        return $serie?->id;
    }

    private function tipoDocumentoIdActual(): int
    {
        return $this->modoDocumento === 'compra'
            ? self::TIPO_FACTURA_COMPRA
            : self::TIPO_FACTURA_VENTA;
    }

    private function idsSeriesPermitidas(): array
    {
        return Serie::query()
            ->activa()
            ->where('documento', 'factura')
            ->where('tipo_documento_id', $this->tipoDocumentoIdActual())
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    private function cargarMedios(): void
    {
        $this->medios = MedioPagos::query()
            ->when(method_exists(MedioPagos::class, 'activos'), fn($q) => $q->activos())
            ->orderBy('nombre')
            ->get([
                'id',
                'codigo',
                'nombre',
                'requiere_turno',
                'contar_en_total',
                'crear_movimiento',
                'tipo_movimiento',
                'clave_turno'
            ]);
    }

    private function queryFacturasPendientes()
    {
        $seriesPermitidas = $this->idsSeriesPermitidas();

        $q = Factura::query()
            ->with(['socioNegocio', 'serie'])
            ->where('saldo', '>', 0)
            ->whereIn('serie_id', $seriesPermitidas);

        if ($this->modoDocumento === 'compra') {
            $q->where(function ($qq) {
                $qq->where('prefijo', 'FAC-C')
                    ->orWhereHas('serie', function ($s) {
                        $s->where('prefijo', 'FAC-C')
                            ->orWhere('tipo_documento_id', self::TIPO_FACTURA_COMPRA);
                    });
            });
        } else {
            $q->where(function ($qq) {
                $qq->where('prefijo', 'FAC-V')
                    ->orWhereHas('serie', function ($s) {
                        $s->where('prefijo', 'FAC-V')
                            ->orWhere('tipo_documento_id', self::TIPO_FACTURA_VENTA);
                    });
            });

            $q->where(function ($qq) {
                $qq->where('tipo_pago', 'credito')
                    ->orWhere('tipo_pago', 'crédito');
            });
        }

        if ($this->serieId) {
            $q->where('serie_id', $this->serieId);
        }

        if (trim($this->buscarFactura) !== '') {
            $b = '%' . trim($this->buscarFactura) . '%';

            $q->where(function ($w) use ($b) {
                $w->whereHas('socioNegocio', function ($qq) use ($b) {
                    $qq->where('razon_social', 'like', $b)
                        ->orWhere('numero_documento', 'like', $b);
                })
                    ->orWhere('numero', 'like', $b)
                    ->orWhere('prefijo', 'like', $b);
            });
        }

        return $q->orderByDesc('fecha')
            ->limit(200)
            ->get([
                'id',
                'numero',
                'prefijo',
                'serie_id',
                'socio_negocio_id',
                'fecha',
                'tipo_pago',
                'total',
                'saldo'
            ]);
    }

    private function cargarFacturaSeleccionada(int $facturaId, bool $rewriteItems = true): void
    {
        $factura = Factura::with(['pagos', 'serie.tipo'])->find($facturaId);

        if (!$factura) {
            $this->resetFacturaYItems();
            return;
        }

        $tipoSerieId = (int) ($factura->serie?->tipo_documento_id ?? 0);

        $esCompraValida = $this->modoDocumento === 'compra'
            && (
                $tipoSerieId === self::TIPO_FACTURA_COMPRA
                || ($factura->prefijo ?? '') === 'FAC-C'
            );

        $esVentaValida = $this->modoDocumento === 'venta'
            && (
                $tipoSerieId === self::TIPO_FACTURA_VENTA
                || ($factura->prefijo ?? '') === 'FAC-V'
            )
            && in_array(mb_strtolower((string) $factura->tipo_pago), ['credito', 'crédito'], true);

        if (!$esCompraValida && !$esVentaValida) {
            $this->facturaId = null;
            $this->resetFacturaYItems();
            return;
        }

        $factura->recalcularTotales()->save();
        $factura->refresh();

        $total  = round((float) ($factura->total ?? 0), 2);
        $pagado = round((float) $factura->pagos()->sum('monto'), 2);
        $saldo  = round(max($total - $pagado, 0), 2);

        $this->fac_total  = $total;
        $this->fac_pagado = $pagado;
        $this->fac_saldo  = $saldo;
        $this->serieId    = (int) $factura->serie_id;

        // Solo reescribe items cuando se selecciona una factura nueva
        if ($rewriteItems) {
            $medioDefault = $this->medios->first()?->id ?? null;

            $this->items = [[
                'medio_pago_id' => $medioDefault,
                'monto'         => round($this->fac_saldo, 2),
                'referencia'    => null,
            ]];
        }

        $this->recalc();
    }

    private function resetFacturaYItems(): void
    {
        $this->fac_total  = 0;
        $this->fac_pagado = 0;
        $this->fac_saldo  = 0;

        $this->items = [[
            'medio_pago_id' => null,
            'porcentaje'    => 0,
            'monto'         => 0,
            'referencia'    => null,
        ]];

        $this->recalc();
    }

    public function addItem(): void
    {
        $this->items[] = [
            'medio_pago_id' => null,
            'porcentaje'    => 0,
            'monto'         => 0,
            'referencia'    => null
        ];

        $this->recalc();
    }

    public function removeItem(int $idx): void
    {
        if (!isset($this->items[$idx])) {
            return;
        }

        array_splice($this->items, $idx, 1);

        if (empty($this->items)) {
            $this->addItem();
        }

        $this->recalc();
    }

    public function updated($name, $value): void
    {
        // Solo recarga si explícitamente cambia facturaId desde el wire:model
        // No recarga cuando cambia items.*.monto para no pisar lo que escribe el usuario
        if ($name === 'facturaId' && $this->facturaId) {
            $this->cargarFacturaSeleccionada((int) $this->facturaId, true);
        }

        $this->recalc();
    }
    private function recalc(): void
    {
        $this->sumPct = 100.00;
        $this->sumMonto = round(collect($this->items)->sum(fn($r) => (float) ($r['monto'] ?? 0)), 2);
        $this->diff = round($this->fac_saldo - $this->sumMonto, 2);
    }

    private function turnoPendienteAnterior(): ?turnos_caja
    {
        return turnos_caja::turnoPendienteDeCerrar();
    }

    private function turnoAbiertoActual(): ?turnos_caja
    {
        return turnos_caja::turnoAbiertoDelDia();
    }

    private function metodoDesdeMedio(?MedioPagos $medio): ?string
    {
        if (!$medio) {
            return null;
        }

        $txt = trim(($medio->codigo ? "{$medio->codigo} - " : '') . ($medio->nombre ?? ''));
        return $txt !== '' ? $txt : null;
    }

    public function guardarPago(): void
    {
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $primerError = collect($e->validator->errors()->all())->first() ?: 'Revisa los datos del pago.';
            PendingToast::create()->error()->message($primerError)->duration(8000);
            throw $e;
        }

        if (!$this->facturaId) {
            PendingToast::create()->warning()
                ->message('Debe seleccionar una factura antes de registrar el pago.')
                ->duration(6000);
            return;
        }

        $factura = Factura::with(['pagos', 'detalles', 'serie.tipo'])->findOrFail($this->facturaId);

        $tipoSerieId = (int) ($factura->serie?->tipo_documento_id ?? 0);

        $esValidaParaModo =
            ($this->modoDocumento === 'venta'  && $tipoSerieId === self::TIPO_FACTURA_VENTA) ||
            ($this->modoDocumento === 'compra' && $tipoSerieId === self::TIPO_FACTURA_COMPRA);

        if (!$esValidaParaModo) {
            PendingToast::create()->warning()
                ->message('La factura seleccionada no corresponde al tipo de documento actual.')
                ->duration(7000);
            return;
        }

        $factura->recalcularTotales()->save();
        $factura->refresh();

        $total  = round((float) ($factura->total ?? 0), 2);
        $pagado = round((float) $factura->pagos()->sum('monto'), 2);
        $saldo  = round(max($total - $pagado, 0), 2);

        $this->fac_total  = $total;
        $this->fac_pagado = $pagado;
        $this->fac_saldo  = $saldo;
        $this->recalc();

        if ($this->fac_saldo <= 0) {
            PendingToast::create()->warning()
                ->message('La factura no tiene saldo pendiente.')
                ->duration(7000);
            return;
        }

        if (round($this->sumMonto, 2) <= 0) {
            PendingToast::create()->warning()
                ->message('El monto a pagar debe ser mayor a cero.')
                ->duration(7000);
            return;
        }

        if (round($this->sumMonto, 2) > round($this->fac_saldo, 2)) {
            PendingToast::create()->warning()
                ->message('El monto a pagar ($' . number_format($this->sumMonto, 2, ',', '.') . ') no puede superar el saldo de la factura ($' . number_format($this->fac_saldo, 2, ',', '.') . ').')
                ->duration(7000);
            return;
        }

        $idsMedios = collect($this->items)->pluck('medio_pago_id')->filter()->values()->all();

        $mediosUsados = empty($idsMedios)
            ? collect()
            : MedioPagos::whereIn('id', $idsMedios)->get();

        $requiereTurno  = $mediosUsados->contains(fn($m) => $this->medioRequiereTurno($m));
        $turnoPendiente = $this->turnoPendienteAnterior();

        if ($requiereTurno && $turnoPendiente) {
            $fecha      = optional($turnoPendiente->fecha_inicio)?->format('d/m/Y');
            $abiertoPor = $turnoPendiente->abiertoPor?->name ?? 'otro usuario';

            PendingToast::create()->warning()
                ->message("Existe una caja abierta del día {$fecha}, abierta por {$abiertoPor}. Debes cerrarla antes de registrar pagos.")
                ->duration(9000);
            return;
        }

        $turno = $this->turnoAbiertoActual();

        if ($requiereTurno && !$turno) {
            PendingToast::create()->warning()
                ->message('No hay una caja abierta para hoy.')
                ->duration(6500);
            return;
        }

        try {
            DB::transaction(function () use (&$factura, $turno, $mediosUsados) {
                $pagos = [];

                foreach ($this->items as $row) {
                    $monto = (float) ($row['monto'] ?? 0);
                    if ($monto <= 0) {
                        continue;
                    }

                    $medio = $mediosUsados->firstWhere('id', (int) ($row['medio_pago_id'] ?? 0))
                        ?: (($row['medio_pago_id'] ?? null)
                            ? MedioPagos::find((int) $row['medio_pago_id'])
                            : null);

                    if (!$medio) {
                        continue;
                    }

                    $asociaTurno = $this->medioRequiereTurno($medio) && $turno;

                    $pago = $factura->registrarPago([
                        'fecha'         => $this->fecha,
                        'medio_pago_id' => (int) ($row['medio_pago_id'] ?? 0),
                        'metodo'        => $this->metodoDesdeMedio($medio),
                        'referencia'    => $row['referencia'] ?? null,
                        'monto'         => $monto,
                        'notas'         => $this->notas,
                        'turno_id'      => $asociaTurno ? $turno->id : null,
                    ]);

                    $pagos[] = $pago;

                    if ($asociaTurno) {
                        $this->acumularEnTurnoDinamico($turno, $medio, $monto, (int) $factura->id);
                    }
                }

                if (empty($pagos)) {
                    throw new \RuntimeException('No se generó ningún pago válido.');
                }

                $factura->refresh();
                $factura->load(['pagos', 'detalles', 'serie.tipo']);

                $asiento = ContabilidadService::asientoDesdePagos($factura, $pagos, 'Pago aplicado a factura');

                foreach ($pagos as $p) {
                    if (Schema::hasColumn($p->getTable(), 'asiento_id')) {
                        $p->update(['asiento_id' => $asiento->id]);
                    }
                }

                $factura->refresh()->recalcularTotales()->save();
                $factura->refresh();
                $factura->load(['pagos', 'detalles', 'serie.tipo']);

                $totalActual    = round((float) ($factura->total ?? 0), 2);
                $pagadoActual   = round((float) $factura->pagos()->sum('monto'), 2);
                $faltanteActual = round($totalActual - $pagadoActual, 2);
                $esContado      = ($factura->tipo_pago ?? '') === 'contado';
                $yaEmitida      = !empty($factura->numero)
                    || in_array(($factura->estado ?? ''), ['emitida', 'pagada', 'anulada'], true);

                if ($esContado && $faltanteActual <= 0.01 && !$yaEmitida) {
                    foreach ($factura->detalles as $idx => $d) {
                        if (empty($d->cuenta_ingreso_id)) {
                            throw new \RuntimeException("La fila #" . ($idx + 1) . " no tiene cuenta de ingreso.");
                        }
                        if (!$d->producto_id || !$d->bodega_id) {
                            throw new \RuntimeException("La fila #" . ($idx + 1) . " debe tener producto y bodega.");
                        }
                        if ((float) ($d->cantidad ?? 0) <= 0) {
                            throw new \RuntimeException("La fila #" . ($idx + 1) . " debe tener una cantidad mayor a cero.");
                        }
                    }

                    \App\Services\InventarioService::verificarDisponibilidadParaFactura($factura);

                    $serie = $factura->serie_id ? Serie::find((int) $factura->serie_id) : null;
                    if (!$serie) {
                        throw new \RuntimeException('No hay una serie válida para emitir este documento.');
                    }

                    $numero = $serie->tomarConsecutivo();
                    $uid    = Auth::id();

                    $dataUpdate = [
                        'serie_id' => $serie->id,
                        'prefijo'  => (string) ($serie->prefijo ?? ''),
                        'numero'   => $numero,
                        'estado'   => 'emitida',
                    ];

                    if (Schema::hasColumn('facturas', 'emitido_por_id')) {
                        $dataUpdate['emitido_por_id'] = $uid;
                    }
                    if (Schema::hasColumn('facturas', 'emitido_en')) {
                        $dataUpdate['emitido_en'] = now();
                    }
                    if (Schema::hasColumn('facturas', 'actualizado_por_id')) {
                        $dataUpdate['actualizado_por_id'] = $uid;
                    }

                    $updated = Factura::query()->whereKey($factura->id)->update($dataUpdate);

                    if (!$updated) {
                        throw new \RuntimeException('No se pudo actualizar la factura con el consecutivo.');
                    }

                    $factura = Factura::with(['pagos', 'detalles', 'serie'])->findOrFail($factura->id);

                    if (is_null($factura->numero) || $factura->numero === '') {
                        throw new \RuntimeException('La factura se intentó emitir, pero el consecutivo no quedó guardado.');
                    }

                    ContabilidadService::asientoDesdeFactura($factura);
                    \App\Services\InventarioService::descontarPorFactura($factura);

                    $factura->recalcularTotales()->save();
                    $factura->refresh();
                }

                $factura->refresh();
                $factura->load('pagos');

                $totalFinal  = round((float) ($factura->total ?? 0), 2);
                $pagadoFinal = round((float) $factura->pagos()->sum('monto'), 2);
                $saldoFinal  = round($totalFinal - $pagadoFinal, 2);

                if ($saldoFinal <= 0.01 && !in_array(($factura->estado ?? ''), ['anulada'], true)) {
                    $dataPagada = ['estado' => 'pagada'];

                    if (Schema::hasColumn('facturas', 'pagado')) {
                        $dataPagada['pagado'] = $pagadoFinal;
                    }
                    if (Schema::hasColumn('facturas', 'saldo')) {
                        $dataPagada['saldo'] = 0;
                    }
                    if (Schema::hasColumn('facturas', 'monto_aplicado')) {
                        $dataPagada['monto_aplicado'] = $pagadoFinal;
                    }

                    $factura->update($dataPagada);
                    $factura->refresh();
                }
            }, 3);
        } catch (\Throwable $e) {
            report($e);

            PendingToast::create()->error()
                ->message('No se pudo registrar y contabilizar el pago: ' . $e->getMessage())
                ->duration(9000);
            return;
        }

        $factura->refresh();

        $mensaje = 'Pago registrado y contabilizado.';

        if (!empty($factura->numero)) {
            $mensaje .= ' Factura emitida automáticamente';
            $mensaje .= (($factura->prefijo ?? '') !== ''
                ? ' ' . $factura->prefijo . '-' . $factura->numero
                : ' ' . $factura->numero) . '.';
        }

        if (($factura->estado ?? '') === 'pagada') {
            $mensaje .= ' La factura quedó pagada completamente.';
        }

        PendingToast::create()->success()->message($mensaje)->duration(6000);

        $this->show = false;
        $this->dispatch('pago-registrado', facturaId: $factura->id);
    }

    private function col(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function boolCfg(?MedioPagos $medio, string $attr, bool $default): bool
    {
        if (!$medio) {
            return $default;
        }

        if (!$this->col($medio->getTable(), $attr)) {
            return $default;
        }

        $v = data_get($medio, $attr);
        $res = filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return is_null($res) ? (bool) $v : $res;
    }

    private function strCfg(?MedioPagos $medio, string $attr, ?string $default = null): ?string
    {
        if (!$medio) {
            return $default;
        }

        if (!$this->col($medio->getTable(), $attr)) {
            return $default;
        }

        $v = trim((string) data_get($medio, $attr));
        return $v !== '' ? $v : $default;
    }

    private function medioRequiereTurno(?MedioPagos $medio): bool
    {
        return $this->boolCfg($medio, 'requiere_turno', false);
    }

    private function medioContarEnTotal(?MedioPagos $medio): bool
    {
        return $this->boolCfg($medio, 'contar_en_total', true);
    }


    private function medioCrearMovimiento(?MedioPagos $medio): bool
    {
        return $this->boolCfg($medio, 'crear_movimiento', false);
    }

    private function medioTipoMovimiento(?MedioPagos $medio): string
    {
        return $this->strCfg($medio, 'tipo_movimiento', 'INGRESO') ?: 'INGRESO';
    }

    private function medioClaveTurno(?MedioPagos $medio): ?string
    {
        return $this->strCfg($medio, 'clave_turno', null);
    }

    private function acumularEnTurnoDinamico(turnos_caja $turno, MedioPagos $medio, float $monto, int $facturaId): void
    {
        if ($this->medioContarEnTotal($medio) && $this->col('turnos_caja', 'total_ventas')) {
            $turno->increment('total_ventas', $monto);
        }

        $col = $this->medioClaveTurno($medio);
        if ($col && $this->col('turnos_caja', $col)) {
            $turno->increment($col, $monto);
        }

        $resumen = (array) ($turno->resumen ?? []);
        $resumen['medios'] = $resumen['medios'] ?? [];

        $mid = (string) $medio->id;
        $prevMonto = (float) ($resumen['medios'][$mid]['monto'] ?? 0);
        $prevMovs  = (int) ($resumen['medios'][$mid]['movimientos'] ?? 0);

        $resumen['medios'][$mid] = [
            'medio_id'    => (int) $medio->id,
            'codigo'      => (string) ($medio->codigo ?? ''),
            'nombre'      => (string) ($medio->nombre ?? ''),
            'monto'       => round($prevMonto + $monto, 2),
            'movimientos' => $prevMovs + 1,
        ];

        $turno->update(['resumen' => $resumen]);

        if ($this->medioCrearMovimiento($medio)) {
            CajaMovimiento::create([
                'turno_id' => $turno->id,
                'user_id'  => Auth::id(),
                'tipo'     => $this->medioTipoMovimiento($medio),
                'monto'    => $monto,
                'motivo'   => 'Pago factura ID ' . $facturaId . ' (' . $this->metodoDesdeMedio($medio) . ')',
            ]);
        }
    }
}
