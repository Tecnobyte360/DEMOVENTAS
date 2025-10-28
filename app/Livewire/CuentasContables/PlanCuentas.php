<?php

namespace App\Livewire\CuentasContables;

use Livewire\Component;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\CuentasContables\PlanCuentas as Cuenta;

class PlanCuentas extends Component
{
    /* ====== Filtros / árbol ====== */
    public string $q = '';
    public ?int $nivelMax = 10;
    public string $naturaleza = 'TODAS';
    public array $expandidos = [];
    public ?int $selectedId = null;

    /* ====== Mejoras UI ====== */
    public bool $soloTitulos = false; // solo cuentas título
    public bool $verSaldos   = false; // mostrar/ocultar columna de saldos
    public array $visibleIds = [];    // soporte a navegación con teclado

    /* ====== Filtro por factura ====== */
    public bool $soloCuentasMovidas = false;
    public ?int $factura_id = null;
    public ?string $factura_prefijo = null;
    public ?int $factura_numero = null;

    protected array $idsCuentasMovidas = [];
    protected array $sumasFacturaPorCuenta = []; // [cuenta_id => ['debe'=>, 'haber'=>]]

    /* ====== Panel ficha (solo display) ====== */
    public ?string $f_codigo = null;
    public ?string $f_nombre = null;
    public ?string $f_moneda = null;
    public bool $f_requiere_tercero = false;
    public int  $f_nivel = 1;
    public bool $f_cuenta_activa = true;
    public bool $f_titulo = false;

    /* ====== Modal crear/editar ====== */
    public bool $showModal = false;
    public ?int $editingId = null;

    // Campos del formulario (editables)
    public ?int $padre_id = null;
    public string $codigo = '';
    public string $nombre = '';
    public string $naturaleza_form = 'ACTIVOS';
    public bool $cuenta_activa = true;
    public bool $titulo = false;
    public string $moneda = 'Pesos Colombianos';
    public bool $requiere_tercero = false;
    public bool $confidencial = false;
    public ?int $nivel_confidencial = null;
    public ?string $clase_cuenta = null;
    public bool $cuenta_monetaria = false;
    public bool $cuenta_asociada = false;
    public bool $revalua_indice = false;
    public bool $bloquear_contab_manual = false;
    public bool $relevante_flujo_caja = false;
    public bool $relevante_costos = false;
    public ?string $dimension1 = null;
    public ?string $dimension2 = null;
    public ?string $dimension3 = null;
    public ?string $dimension4 = null;
    public float $saldo = 0;

    public function mount(): void
    {
        // abre raíces por defecto
        $this->expandidos = Cuenta::whereNull('padre_id')->pluck('id')->all();
    }

    /* ===== Helper de orden compatible con cualquier motor ===== */
    protected function ordenCodigoExpr(int $padLen = 12): string
    {
        $col = "REPLACE(codigo, '.', '')";
        switch (DB::getDriverName()) {
            case 'sqlsrv': // SQL Server
                return "RIGHT(REPLICATE('0', {$padLen}) + CAST($col AS VARCHAR(64)), {$padLen})";
            case 'pgsql':  // PostgreSQL
            case 'mysql':  // MySQL/MariaDB
                return "LPAD($col, {$padLen}, '0')";
            case 'sqlite': // SQLite
                $zeros = str_repeat('0', $padLen);
                return "substr('{$zeros}' || $col, -{$padLen})";
            default:
                return $col;
        }
    }

    /* ========== Validación ========== */
    protected function rules(): array
    {
        return [
            'padre_id' => ['nullable','integer','exists:plan_cuentas,id'],
            'codigo' => ['required','string','max:30', Rule::unique('plan_cuentas','codigo')->ignore($this->editingId)],
            'nombre' => ['required','string','max:255'],
            'naturaleza_form' => ['required','string','max:40'],
            'cuenta_activa' => ['boolean'],
            'titulo' => ['boolean'],
            'moneda' => ['required','string','max:50'],
            'requiere_tercero' => ['boolean'],
            'confidencial' => ['boolean'],
            'nivel_confidencial' => ['nullable','integer','between:0,10'],
            'clase_cuenta' => ['nullable','string','max:40'],
            'cuenta_monetaria' => ['boolean'],
            'cuenta_asociada' => ['boolean'],
            'revalua_indice' => ['boolean'],
            'bloquear_contab_manual' => ['boolean'],
            'relevante_flujo_caja' => ['boolean'],
            'relevante_costos' => ['boolean'],
            'dimension1' => ['nullable','string','max:255'],
            'dimension2' => ['nullable','string','max:255'],
            'dimension3' => ['nullable','string','max:255'],
            'dimension4' => ['nullable','string','max:255'],
            'saldo' => ['numeric','min:0'],
        ];
    }

    /* ===== casting para “Todos” en nivel ===== */
    public function updatedNivelMax($v): void
    {
        $this->nivelMax = ($v === '' || $v === null) ? null : (int)$v;
    }

    /* ===== reactividad ficha ===== */
    public function updatedSelectedId(): void
    {
        $this->cargarFicha($this->selectedId);
    }

    protected function cargarFicha(?int $id): void
    {
        if (!$id) { $this->resetFicha(); return; }
        $c = Cuenta::find($id);
        if (!$c) { $this->resetFicha(); return; }

        $this->f_codigo = $c->codigo;
        $this->f_nombre = $c->nombre;
        $this->f_moneda = $c->moneda;
        $this->f_requiere_tercero = (bool) $c->requiere_tercero;
        $this->f_nivel = (int) $c->nivel;
        $this->f_cuenta_activa = (bool) $c->cuenta_activa;
        $this->f_titulo = (bool) $c->titulo;
    }

    protected function resetFicha(): void
    {
        $this->f_codigo = $this->f_nombre = $this->f_moneda = null;
        $this->f_requiere_tercero = false;
        $this->f_nivel = 1;
        $this->f_cuenta_activa = true;
        $this->f_titulo = false;
    }

    /* ===== Filtros/Naturaleza ===== */
    public function setNaturaleza(string $nat): void
    {
        $this->naturaleza = strtoupper($nat);
        $this->expandidos = Cuenta::whereNull('padre_id')
            ->when($this->naturaleza !== 'TODAS', fn($q) => $q->where('naturaleza', $this->naturaleza))
            ->pluck('id')->all();
    }

    public function toggle(int $id): void
    {
        if (in_array($id, $this->expandidos)) {
            $this->expandidos = array_values(array_diff($this->expandidos, [$id]));
        } else {
            $this->expandidos[] = $id;
        }
    }

    public function expandAll(): void
    {
        $this->expandidos = Cuenta::when($this->naturaleza !== 'TODAS', fn($q) => $q->where('naturaleza', $this->naturaleza))
            ->pluck('id')->all();
    }

    public function collapseAll(): void
    {
        $this->expandidos = Cuenta::whereNull('padre_id')
            ->when($this->naturaleza !== 'TODAS', fn($q) => $q->where('naturaleza', $this->naturaleza))
            ->pluck('id')->all();
    }

    /* ======= Árbol (con withCount y filtros de UI) ======= */
    protected function buildFlatTree()
    {
        $base = Cuenta::query()
            ->withCount('hijos') // para badge sin N+1
            ->when($this->naturaleza !== 'TODAS', fn($q) => $q->where('naturaleza', $this->naturaleza))
            ->when($this->soloTitulos, fn($q) => $q->where('titulo', true))
            ->when($this->q !== '', function ($q) {
                $t = trim($this->q);
                $q->where(fn($qq) => $qq->where('codigo','like',"%{$t}%")->orWhere('nombre','like',"%{$t}%"));
            })
            ->orderByRaw($this->ordenCodigoExpr().' ASC')
            ->get()
            ->groupBy('padre_id');

        $flat = [];
        $walk = function ($padreId, $nivel) use (&$walk, &$flat, $base) {
            foreach (($base[$padreId] ?? collect()) as $nodo) {
                if ($this->nivelMax !== null && $nivel > $this->nivelMax) continue;
                $nodo->nivel_visual = $nivel; // para UI
                $flat[] = $nodo;
                if (in_array($nodo->id, $this->expandidos)) $walk($nodo->id, $nivel + 1);
            }
        };
        $walk(null, 1);
        return collect($flat);
    }

    /* ===== Selección fila ===== */
    public function select(int $id): void
    {
        $this->selectedId = $id;
        $this->cargarFicha($id);
    }

    /** IDs de todos los descendientes (evitar ciclos) */
    protected function descendantIdsOf(int $id): array
    {
        $ids = [];
        $hijos = Cuenta::where('padre_id', $id)->pluck('id')->all();
        foreach ($hijos as $h) {
            $ids[] = $h;
            $ids = array_merge($ids, $this->descendantIdsOf($h));
        }
        return $ids;
    }

    /* ===== Crear/Editar ===== */
    public function openCreate(?int $padreId = null): void
    {
        $this->resetForm();
        $this->padre_id = $padreId;

        if ($padreId) {
            $padre = Cuenta::find($padreId);
            if ($padre) {
                $this->naturaleza_form = $padre->naturaleza ?: $this->naturaleza_form;
                $this->moneda = $padre->moneda ?: $this->moneda;
                $this->codigo = $this->sugerirCodigoPara($padreId);
            }
        }
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->resetForm();
        $c = Cuenta::findOrFail($id);

        $this->editingId = $c->id;
        $this->padre_id = $c->padre_id;
        $this->codigo = $c->codigo;
        $this->nombre = $c->nombre;
        $this->naturaleza_form = $c->naturaleza;
        $this->cuenta_activa = (bool)$c->cuenta_activa;
        $this->titulo = (bool)$c->titulo;
        $this->moneda = $c->moneda;
        $this->requiere_tercero = (bool)$c->requiere_tercero;
        $this->confidencial = (bool)$c->confidencial;
        $this->nivel_confidencial = $c->nivel_confidencial;
        $this->clase_cuenta = $c->clase_cuenta;
        $this->cuenta_monetaria = (bool)$c->cuenta_monetaria;
        $this->cuenta_asociada = (bool)$c->cuenta_asociada;
        $this->revalua_indice = (bool)$c->revalua_indice;
        $this->bloquear_contab_manual = (bool)$c->bloquear_contab_manual;
        $this->relevante_flujo_caja = (bool)$c->relevante_flujo_caja;
        $this->relevante_costos = (bool)$c->relevante_costos;
        $this->dimension1 = $c->dimension1;
        $this->dimension2 = $c->dimension2;
        $this->dimension3 = $c->dimension3;
        $this->dimension4 = $c->dimension4;
        $this->saldo = (float)$c->saldo;

        $this->showModal = true;
    }

    /** Reacciona al cambio de padre en el formulario */
    public function updatedPadreId($val): void
    {
        $nuevoPadre = $val ? (int)$val : null;

        if (!$this->editingId && $nuevoPadre) {
            $p = Cuenta::find($nuevoPadre);
            if ($p) {
                $this->codigo = $this->sugerirCodigoPara($nuevoPadre);
                $this->naturaleza_form = $p->naturaleza ?: $this->naturaleza_form;
                $this->moneda = $p->moneda ?: $this->moneda;
            }
        }
    }

    /** Sugerir próximo código disponible para un padre dado */
    protected function sugerirCodigoPara(int $padreId): string
    {
        $padre = Cuenta::find($padreId);
        if (!$padre) return $this->codigo;

        $hijos = Cuenta::where('padre_id', $padreId)
            ->orderByRaw($this->ordenCodigoExpr().' ASC')
            ->pluck('codigo')->all();

        if (empty($hijos)) return rtrim($padre->codigo) . '01';

        $ultimo = end($hijos);
        if (preg_match('/^(.*?)(\d+)$/', $ultimo, $m)) {
            $pref = $m[1]; $num  = $m[2];
            $next = str_pad((string)((int)$num + 1), strlen($num), '0', STR_PAD_LEFT);
            return $pref . $next;
        }
        return $ultimo . '01';
    }

    public function save(): void
    {
        $this->validate();

        $old = null;
        if ($this->editingId) {
            if ($this->padre_id === $this->editingId) {
                $this->addError('padre_id', 'La cuenta no puede ser su propio padre.');
                return;
            }
            if ($this->padre_id) {
                $desc = $this->descendantIdsOf($this->editingId);
                if (in_array($this->padre_id, $desc)) {
                    $this->addError('padre_id', 'No puedes asignar como padre a un descendiente.');
                    return;
                }
            }
            $old = Cuenta::findOrFail($this->editingId);
        }

        $nivel = 1;
        if ($this->padre_id) {
            $padre = Cuenta::findOrFail($this->padre_id);
            $nivel = (int)$padre->nivel + 1;
        }

        $data = [
            'padre_id' => $this->padre_id,
            'codigo' => trim($this->codigo),
            'nombre' => $this->nombre,
            'nivel' => $nivel,
            'naturaleza' => strtoupper($this->naturaleza_form),
            'cuenta_activa' => $this->cuenta_activa,
            'titulo' => $this->titulo,
            'moneda' => $this->moneda,
            'requiere_tercero' => $this->requiere_tercero,
            'confidencial' => $this->confidencial,
            'nivel_confidencial' => $this->nivel_confidencial,
            'clase_cuenta' => $this->clase_cuenta,
            'cuenta_monetaria' => $this->cuenta_monetaria,
            'cuenta_asociada' => $this->cuenta_asociada,
            'revalua_indice' => $this->revalua_indice,
            'bloquear_contab_manual' => $this->bloquear_contab_manual,
            'relevante_flujo_caja' => $this->relevante_flujo_caja,
            'relevante_costos' => $this->relevante_costos,
            'dimension1' => $this->dimension1,
            'dimension2' => $this->dimension2,
            'dimension3' => $this->dimension3,
            'dimension4' => $this->dimension4,
            'saldo' => $this->saldo,
        ];

        DB::transaction(function () use ($data, $old) {
            if ($this->editingId) {
                $cuenta = Cuenta::findOrFail($this->editingId);
                $padreCambio = $old?->padre_id !== $this->padre_id;
                $cuenta->update($data);
                if ($padreCambio) $this->relevelSubtree($cuenta->id, $data['nivel']);
                $idFinal = $cuenta->id;
                $this->expandPathAndSelect($idFinal);
            } else {
                $cuenta = Cuenta::create($data);
                $this->expandPathAndSelect($cuenta->id);
            }
        });

        $this->showModal = false;
        $this->resetForm();
        if ($this->selectedId) $this->cargarFicha($this->selectedId);

        $this->dispatch('toast', title: 'Guardado', message: 'La cuenta se guardó correctamente.');
    }

    protected function relevelSubtree(int $id, int $nivelBase): void
    {
        $hijos = Cuenta::where('padre_id', $id)->get(['id','nivel']);
        foreach ($hijos as $h) {
            $nuevoNivel = $nivelBase + 1;
            Cuenta::whereKey($h->id)->update(['nivel' => $nuevoNivel]);
            $this->relevelSubtree($h->id, $nuevoNivel);
        }
    }

    protected function expandPathAndSelect(int $id): void
    {
        $ruta = [];
        $n = Cuenta::with('padre')->find($id);
        while ($n) { $ruta[] = $n->id; $n = $n->padre; }
        $this->expandidos = array_values(array_unique(array_merge($this->expandidos, $ruta)));
        $this->selectedId = $id;
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId','padre_id','codigo','nombre','naturaleza_form','cuenta_activa','titulo','moneda',
            'requiere_tercero','confidencial','nivel_confidencial','clase_cuenta','cuenta_monetaria','cuenta_asociada',
            'revalua_indice','bloquear_contab_manual','relevante_flujo_caja','relevante_costos',
            'dimension1','dimension2','dimension3','dimension4','saldo'
        ]);
        $this->naturaleza_form = 'ACTIVOS';
        $this->cuenta_activa = true;
        $this->titulo = false;
        $this->moneda = 'Pesos Colombianos';
        $this->saldo = 0;
    }

    /* ========== Filtro por FACTURA ========== */
    protected function cargarCuentasDeFactura(): void
    {
        $this->idsCuentasMovidas = [];
        $this->sumasFacturaPorCuenta = [];
        if (!$this->soloCuentasMovidas) return;

        $q = DB::table('asientos as a')
            ->join('movimientos as m', 'm.asiento_id', '=', 'a.id')
            ->when($this->factura_id, fn($qq) =>
                $qq->where('a.origen', 'factura')->where('a.origen_id', $this->factura_id)
            )
            ->when(!$this->factura_id && $this->factura_prefijo && $this->factura_numero, fn($qq) =>
                $qq->join('facturas as f', 'f.id', '=', 'a.origen_id')
                   ->where('a.origen', 'factura')
                   ->where('f.prefijo', $this->factura_prefijo)
                   ->where('f.numero',  $this->factura_numero)
            )
            ->groupBy('m.cuenta_id')
            ->selectRaw('m.cuenta_id, SUM(m.debe) AS debe, SUM(m.haber) AS haber')
            ->get();

        foreach ($q as $r) {
            $cid = (int)$r->cuenta_id;
            $this->idsCuentasMovidas[] = $cid;
            $this->sumasFacturaPorCuenta[$cid] = ['debe' => (float)$r->debe, 'haber' => (float)$r->haber];
        }
    }

    public function filtrarPorFacturaId(int $id): void
    {
        $this->factura_id = $id;
        $this->factura_prefijo = null;
        $this->factura_numero  = null;
        $this->soloCuentasMovidas = true;
    }

    public function filtrarPorFacturaNum(string $prefijo, int $numero): void
    {
        $this->factura_id = null;
        $this->factura_prefijo = $prefijo;
        $this->factura_numero  = $numero;
        $this->soloCuentasMovidas = true;
    }

    public function limpiarFiltroFactura(): void
    {
        $this->soloCuentasMovidas = false;
        $this->factura_id = null;
        $this->factura_prefijo = null;
        $this->factura_numero = null;
    }

    /* ======== Controles extra de árbol (slider/teclado/breadcrumbs) ======== */
    public function expandToLevel(int $level): void
    {
        $level = max(1, min(10, $level));
        $ids = Cuenta::query()
            ->where('nivel', '<', $level)
            ->when($this->naturaleza !== 'TODAS', fn($q) => $q->where('naturaleza',$this->naturaleza))
            ->pluck('id')->all();
        $this->expandidos = array_values(array_unique($ids));
    }

    public function toggleSelectedByKey(bool $expand): void
    {
        if (!$this->selectedId) return;
        $isOpen = in_array($this->selectedId, $this->expandidos);
        if ($expand && !$isOpen) $this->expandidos[] = $this->selectedId;
        if (!$expand && $isOpen) $this->expandidos = array_values(array_diff($this->expandidos, [$this->selectedId]));
    }

    public function selectNext(): void
    {
        if (empty($this->visibleIds)) return;
        $idx = array_search($this->selectedId, $this->visibleIds, true);
        $next = $this->visibleIds[min(($idx===false? -1 : $idx)+1, count($this->visibleIds)-1)];
        $this->select($next);
        $this->dispatch('pc-scroll-to-selected', id: $next);
    }

    public function selectPrev(): void
    {
        if (empty($this->visibleIds)) return;
        $idx = array_search($this->selectedId, $this->visibleIds, true);
        $prev = $this->visibleIds[max(($idx===false? 1 : $idx)-1, 0)];
        $this->select($prev);
        $this->dispatch('pc-scroll-to-selected', id: $prev);
    }

    protected function rutaSeleccionadaArray(?int $id): array
    {
        if (!$id) return [];
        $ruta = [];
        $n = Cuenta::with('padre')->find($id);
        while ($n) { $ruta[] = ['id'=>$n->id, 'codigo'=>$n->codigo, 'nombre'=>$n->nombre]; $n = $n->padre; }
        return array_reverse($ruta);
    }

    /* ========== Render ========== */
    public function render()
    {
        $this->cargarCuentasDeFactura();

        $items = $this->buildFlatTree();

        if ($this->soloCuentasMovidas && !empty($this->idsCuentasMovidas)) {
            $items = $items->whereIn('id', $this->idsCuentasMovidas)->values();
        }

        $naturDeudoras = ['D','DEUDORA','ACTIVO','ACTIVOS','GASTO','GASTOS','COSTO','COSTOS','INVENTARIO'];
        $items = $items->map(function ($row) use ($naturDeudoras) {
            $sum = $this->sumasFacturaPorCuenta[$row->id] ?? ['debe'=>0.0,'haber'=>0.0];
            $bruto = $sum['debe'] - $sum['haber'];
            $nat = strtoupper((string) $row->naturaleza);
            $esDeudora = in_array($nat, $naturDeudoras);
            $delta = $esDeudora ? $bruto : -$bruto;

            $row->saldo_despues = round((float)$row->saldo, 2);
            $row->saldo_antes   = round($row->saldo_despues - $delta, 2);
            $row->saldo_delta   = round($delta, 2);
            return $row;
        });

        // visibilidad para navegación con teclado
        $this->visibleIds = $items->pluck('id')->all();

        // posibles padres (modal) — orden portable
        $posiblesPadres = Cuenta::query()
            ->when($this->editingId, function ($q) {
                $q->where('id', '!=', $this->editingId);
                $desc = $this->descendantIdsOf($this->editingId);
                if (!empty($desc)) $q->whereNotIn('id', $desc);
            })
            ->orderByRaw($this->ordenCodigoExpr().' ASC')
            ->get(['id','codigo','nombre']);

        $nivelMax = $this->nivelMax;
        $rutaSeleccionada = $this->rutaSeleccionadaArray($this->selectedId);

        return view('livewire.cuentas-contables.plan-cuentas', compact('items','nivelMax','posiblesPadres','rutaSeleccionada'));
    }
}
