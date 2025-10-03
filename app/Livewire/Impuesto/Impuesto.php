<?php

namespace App\Livewire\Impuesto;

use Livewire\Component;
use App\Models\Impuestos\Impuesto as ImpuestoModel;     // tabla: impuestos
use App\Models\Impuesto\ImpuestoTipo as TipoModel;       // tabla: impuesto_tipos
use App\Models\CuentasContables\PlanCuentas as Cuenta;   // tabla: plan_cuentas

class Impuesto extends Component
{
    /* ========= Filtros ========= */
    public string $q = '';
    public string $filtroTipo = 'TODOS';   // id numérico o 'TODOS'
    public string $filtroAplica = 'TODOS'; // VENTAS | COMPRAS | AMBOS | TODOS
    public bool $soloActivos = true;

    /* ========= Modal / Form ========= */
    public bool $showModal = false;
    public ?int $editingId = null;

    public ?int $tipo_id = null;
    public string $codigo = '';
    public string $nombre = '';
    public string $aplica_sobre = 'AMBOS';
    public ?float $porcentaje = null;
    public ?float $monto_fijo = null;
    public bool $incluido_en_precio = false;
    public string $regla_redondeo = 'NORMAL';
    public ?string $vigente_desde = null;
    public ?string $vigente_hasta = null;
    public bool $activo = true;
    public int $prioridad = 1;
    public ?int $cuenta_id = null;
    public ?int $contracuenta_id = null;

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->resetForm();
        $this->editingId = $id;

        $i = ImpuestoModel::findOrFail($id);
        $this->fill([
            'tipo_id'          => $i->tipo_id,
            'codigo'           => $i->codigo,
            'nombre'           => $i->nombre,
            'aplica_sobre'     => $i->aplica_sobre,
            'porcentaje'       => $i->porcentaje,
            'monto_fijo'       => $i->monto_fijo,
            'incluido_en_precio' => $i->incluido_en_precio,
            'regla_redondeo'   => $i->regla_redondeo,
            'vigente_desde'    => optional($i->vigente_desde)->format('Y-m-d'),
            'vigente_hasta'    => optional($i->vigente_hasta)->format('Y-m-d'),
            'activo'           => $i->activo,
            'prioridad'        => (int) $i->prioridad,
            'cuenta_id'        => $i->cuenta_id,
            'contracuenta_id'  => $i->contracuenta_id,
        ]);

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate($this->rules(), $this->messages());

        // Regla de negocio: porcentaje XOR monto_fijo
        $p = $this->porcentaje; $m = $this->monto_fijo;
        if (($p === null || $p === '') && ($m === null || $m === '')) {
            $this->addError('porcentaje', 'Define porcentaje o monto fijo.');
            $this->addError('monto_fijo', 'Define porcentaje o monto fijo.');
            return;
        }
        if ($p !== null && $p !== '' && $m !== null && $m !== '') {
            $this->addError('monto_fijo', 'Usa solo uno: porcentaje o monto fijo.');
            return;
        }

        $data = [
            'tipo_id'          => $this->tipo_id,
            'codigo'           => trim($this->codigo),
            'nombre'           => trim($this->nombre),
            'aplica_sobre'     => $this->aplica_sobre,
            'porcentaje'       => ($this->porcentaje === '' ? null : $this->porcentaje),
            'monto_fijo'       => ($this->monto_fijo === '' ? null : $this->monto_fijo),
            'incluido_en_precio' => $this->incluido_en_precio,
            'regla_redondeo'   => $this->regla_redondeo,
            'vigente_desde'    => $this->vigente_desde ?: null,
            'vigente_hasta'    => $this->vigente_hasta ?: null,
            'activo'           => $this->activo,
            'prioridad'        => (int) $this->prioridad,
            'cuenta_id'        => $this->cuenta_id,
            'contracuenta_id'  => $this->contracuenta_id ?: null,
        ];

        ImpuestoModel::updateOrCreate(['id' => $this->editingId], $data);

        $this->dispatch('saved');
        $this->showModal = false;
        $this->resetForm();
    }

    protected function rules(): array
    {
        $unique = 'unique:impuestos,codigo';
        if ($this->editingId) $unique = 'unique:impuestos,codigo,'.$this->editingId;

        return [
            'tipo_id'          => ['required','exists:impuesto_tipos,id'],
            'codigo'           => ['required','max:20',$unique],
            'nombre'           => ['required','max:255'],
            'aplica_sobre'     => ['required','in:VENTAS,COMPRAS,AMBOS'],
            'porcentaje'       => ['nullable','numeric','min:0','max:999.9999'],
            'monto_fijo'       => ['nullable','numeric','min:0'],
            'incluido_en_precio' => ['boolean'],
            'regla_redondeo'   => ['required','in:NORMAL,ARRIBA,ABAJO'],
            'vigente_desde'    => ['nullable','date'],
            'vigente_hasta'    => ['nullable','date','after_or_equal:vigente_desde'],
            'activo'           => ['boolean'],
            'prioridad'        => ['integer','min:1','max:255'],
            'cuenta_id'        => ['required','exists:plan_cuentas,id'],
            'contracuenta_id'  => ['nullable','exists:plan_cuentas,id'],
        ];
    }

    protected function messages(): array
    {
        return [
            'tipo_id.required'  => 'Selecciona el tipo de impuesto.',
            'cuenta_id.required'=> 'Selecciona la cuenta contable asociada.',
            'codigo.unique'     => 'Este código ya existe.',
        ];
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId','tipo_id','codigo','nombre','aplica_sobre','porcentaje','monto_fijo',
            'incluido_en_precio','regla_redondeo','vigente_desde','vigente_hasta',
            'activo','prioridad','cuenta_id','contracuenta_id'
        ]);
        $this->aplica_sobre      = 'AMBOS';
        $this->regla_redondeo    = 'NORMAL';
        $this->prioridad         = 1;
        $this->activo            = true;
        $this->incluido_en_precio= false;
    }

    public function render()
    {
        $tipos = TipoModel::query()->orderBy('orden')->orderBy('nombre')->get();

        // Cuentas: solo imputables y activas
        $cuentas = Cuenta::where('titulo', false)
            ->where('cuenta_activa', true)
            ->orderBy('codigo')
            ->limit(1500)
            ->get();

        $items = ImpuestoModel::with(['tipo','cuenta'])
            ->when($this->q !== '', fn($q) => $q->where(fn($qq) =>
                $qq->where('codigo','like',"%{$this->q}%")
                   ->orWhere('nombre','like',"%{$this->q}%")
            ))
            ->when($this->filtroTipo !== 'TODOS' && is_numeric($this->filtroTipo),
                fn($q) => $q->where('tipo_id',(int)$this->filtroTipo)
            )
            ->when($this->filtroAplica !== 'TODOS',
                fn($q) => $q->where('aplica_sobre',$this->filtroAplica)
            )
            ->when($this->soloActivos,
                fn($q)=> $q->where('activo', true)
            )
            ->orderBy('prioridad')->orderBy('codigo')
            ->get();

        return view('livewire.impuesto.impuesto', [
            'items'   => $items,
            'tipos'   => $tipos,
            'cuentas' => $cuentas,
        ]);
    }
}
