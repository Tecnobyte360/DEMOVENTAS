<?php

namespace App\Livewire\SocioNegocio;

use App\Models\SocioNegocio\SocioNegocioCuenta;
use App\Models\Pedidos\Pedido;
use App\Models\CuentasContables\PlanCuentas;
use App\Models\SocioNegocio\SocioNegocio;
use Livewire\Component;
use Livewire\WithFileUploads;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SocioNegocios extends Component
{
    use WithFileUploads;

    /** Catálogo para TomSelect (selector puntual) */
    public array $clientesFiltrados = [];

    /** Listas separadas */
    public $clientes = [];
    public $proveedores = [];

    /** Import */
    public $importFile;
    public bool $isLoading = false;

    /** Filtros compartidos */
    public ?int $socioNegocioId = null;
    public string $buscador = '';

    /** Pedidos (modal) */
    public ?int $socioPedidosId = null;
    public array $pedidosSocio = [];
    public bool $mostrarDetalleModal = false;
    public ?Pedido $pedidoSeleccionado = null;
    public array $detallesPedido = [];

    /** ===== Modal: Cuentas contables ===== */
    public bool $showCuentasModal = false;
    public ?int $socioCuentasId = null;

    // Campos seleccionados
    public ?int $cuenta_cxc_id = null;
    public ?int $cuenta_anticipos_id = null;
    public ?int $cuenta_descuentos_id = null;
    public ?int $cuenta_ret_fuente_id = null;
    public ?int $cuenta_ret_ica_id = null;
    public ?int $cuenta_iva_id = null;

    // Opciones para selects de plan de cuentas
    public array $cuentasOptions = [];

    /** ===== Modal: Direcciones (CRUD hijo) ===== */
    public ?int $socioDireccionesId = null;
    public bool $showDireccionesModal = false;

    /** Catálogos (si los usas desde esta vista) */
    public array $municipiosOptions = [];
    public array $ciiuOptions = [];

    /** ===== Modal: Condiciones de Pago (NUEVO) ===== */
    public bool $showCondicionesModal = false;
    public ?int $socioCondicionesId = null;

    // Campos condiciones de pago (NUEVO)
    public string $condicion_pago = 'contado'; // contado|credito
    public ?int $plazo_dias = null;
    public ?float $interes_mora_pct = null;     // % mensual
    public ?float $limite_credito = null;
    public ?int $tolerancia_mora_dias = null;
    public ?int $dia_corte = null;

    /** Listeners hijo ↔ padre */
    protected $listeners = [
        'abrirDirecciones' => 'abrirDirecciones',
        'direccionesActualizadas' => 'loadListas',

        // Abrir modal condiciones desde la vista (botón/acción)
        'abrirCondicionesPago' => 'abrirCondicionesPago', // $emit('abrirCondicionesPago', socioId)
    ];

    public function mount(): void
    {
        try {
            $this->loadClientesSelect();
            $this->loadCuentasOptions();
            $this->loadCatalogos();
            $this->loadListas();
        } catch (\Throwable $e) {
            $this->handleException($e, 'No fue posible iniciar el módulo de socios.');
        }
    }

    /** === Catálogo TomSelect === */
    public function loadClientesSelect(): void
    {
        try {
            $this->clientesFiltrados = SocioNegocio::select('id', 'razon_social', 'nit')
                ->orderBy('razon_social')
                ->get()
                ->toArray();
        } catch (\Throwable $e) {
            $this->clientesFiltrados = [];
            $this->handleException($e, 'No se pudo cargar el catálogo de socios.');
        }
    }

    /** === Opciones Plan de Cuentas para los selects === */
    public function loadCuentasOptions(): void
    {
        try {
            $this->cuentasOptions = PlanCuentas::query()
                ->when(method_exists(PlanCuentas::class, 'scopeImputables'), fn($q) => $q->imputables())
                ->orderBy('codigo')
                ->get(['id','codigo','nombre'])
                ->map(fn($c)=>[
                    'id'    => $c->id,
                    'label' => $c->codigo.' — '.$c->nombre
                ])->toArray();
        } catch (\Throwable $e) {
            $this->cuentasOptions = [];
            $this->handleException($e, 'No se pudieron cargar las cuentas contables.');
        }
    }

    /** === Catálogos (municipios/CIIU) === */
    public function loadCatalogos(): void
    {
        try {
            // Reemplaza por tus tablas reales si aplica.
            $this->municipiosOptions = [
                ['id'=>1,'label'=>'Medellín (Antioquia)'],
                ['id'=>2,'label'=>'Itagüí (Antioquia)'],
                ['id'=>3,'label'=>'La Estrella (Antioquia)'],
            ];

            $this->ciiuOptions = [
                ['value'=>'2511','label'=>'2511 - Fabricación de productos metálicos'],
                ['value'=>'3320','label'=>'3320 - Instalación de maquinaria y equipo'],
            ];
        } catch (\Throwable $e) {
            $this->municipiosOptions = [];
            $this->ciiuOptions = [];
            $this->handleException($e, 'No se pudieron cargar los catálogos de municipios/CIIU.');
        }
    }

    /** === Carga dos columnas (clientes y proveedores) con filtros compartidos === */
    public function loadListas(): void
    {
        try {
            $withRels = [
                'pedidos.pagos',
                'pedidos.detalles.producto',
                'pedidos.usuario',
                'pedidos.ruta',
                'cuentas.cuentaCxc',
                'cuentas.cuentaAnticipos',
                'cuentas.cuentaDescuentos',
                'cuentas.cuentaRetFuente',
                'cuentas.cuentaRetIca',
                'cuentas.cuentaIva',
                'direcciones',
            ];

            $base = SocioNegocio::with($withRels)
                ->when($this->socioNegocioId, fn($q) => $q->where('id', $this->socioNegocioId))
                ->when(trim($this->buscador) !== '', function ($q) {
                    $t = '%'.trim($this->buscador).'%';
                    $q->where(function ($qq) use ($t) {
                        $qq->where('razon_social', 'like', $t)
                           ->orWhere('nit', 'like', $t)
                           ->orWhere('direccion', 'like', $t);
                    });
                })
                ->orderBy('razon_social')
                ->get();

            $enriquecer = function ($s) {
                try {
                    // saldo pendiente de crédito
                    $creditos = $s->pedidos
                        ->where('tipo_pago', 'credito')
                        ->whereNull('cancelado')
                        ->filter(function ($p) {
                            $total  = $p->detalles->sum(fn($d) => $d->cantidad * floatval($d->precio_aplicado ?? $d->precio_unitario));
                            $pagado = $p->pagos->sum('monto');
                            return $total > $pagado;
                        })
                        ->map(function ($p) {
                            $total  = $p->detalles->sum(fn($d) => $d->cantidad * floatval($d->precio_aplicado ?? $d->precio_unitario));
                            $pagado = $p->pagos->sum('monto');
                            return ['id' => $p->id, 'total_raw' => max(0, $total - $pagado)];
                        })
                        ->values();

                    $s->creditosPendientes = $creditos;
                    $s->saldoPendiente     = $creditos->sum('total_raw');

                    // dirección de entrega principal
                    $s->dirEntregaPrincipal = optional(
                        $s->direcciones->where('tipo','entrega')->where('es_principal',true)->first()
                    );

                    return $s;
                } catch (\Throwable $e) {
                    Log::warning('Fallo enriqueciendo socio '.$s->id.': '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
                    $s->creditosPendientes = collect();
                    $s->saldoPendiente = 0;
                    $s->dirEntregaPrincipal = null;
                    return $s;
                }
            };

            $base = $base->map($enriquecer);

            $this->clientes    = $base->filter(fn($s) => strtoupper(trim($s->tipo ?? '')) === 'C')->values();
            $this->proveedores = $base->filter(fn($s) => strtoupper(trim($s->tipo ?? '')) === 'P')->values();
        } catch (\Throwable $e) {
            $this->clientes = collect();
            $this->proveedores = collect();
            $this->handleException($e, 'No se pudieron cargar los listados de socios.');
        }
    }

    /** Re-carga al cambiar filtros */
    public function updatedSocioNegocioId(): void
    {
        try { $this->loadListas(); }
        catch (\Throwable $e) { $this->handleException($e, 'Error al filtrar por socio.'); }
    }

    public function updatedBuscador(): void
    {
        try { $this->loadListas(); }
        catch (\Throwable $e) { $this->handleException($e, 'Error al aplicar el buscador.'); }
    }

    /** Acciones */
    public function editsocio(int $id): void
    {
        try {
            $this->dispatch('loadEditSocio', $id);
        } catch (\Throwable $e) {
            $this->handleException($e, 'No se pudo abrir la edición del socio.');
        }
    }

    public function import(): void
    {
        try {
            $this->validate(['importFile' => 'required|file|mimes:csv,txt']);
            $this->isLoading = true;

            DB::transaction(function () {
                // tu lógica real de importación
                usleep(300000); // demo breve
            });

            session()->flash('message', 'Socios importados correctamente.');
            $this->loadClientesSelect();
            $this->loadListas();
            $this->reset('importFile');
        } catch (ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $e) {
            $this->handleException($e, 'Hubo un problema importando el archivo.');
        } finally {
            $this->isLoading = false;
        }
    }

    public function cancelar(): void
    {
        try {
            $this->reset(['importFile']);
            $this->resetValidation();
            session()->forget(['message','error','errores_importacion']);
            $this->dispatchBrowserEvent('close-import-modal');
        } catch (\Throwable $e) {
            $this->handleException($e, 'No se pudo cancelar la importación.');
        }
    }

    /** ===== Pedidos del socio ===== */
    public function mostrarPedidos(int $socioId): void
    {
        try {
            $this->socioPedidosId = $socioId;

            $socio = SocioNegocio::with([
                'pedidos.pagos','pedidos.usuario','pedidos.ruta','pedidos.detalles.producto'
            ])->find($socioId);

            if (!$socio) { $this->pedidosSocio = []; return; }

            $this->pedidosSocio = $socio->pedidos
                ->where('tipo_pago', 'credito')->whereNull('cancelado')
                ->filter(function ($p) {
                    $total  = $p->detalles->sum(fn($d) => $d->cantidad * floatval($d->precio_aplicado ?? $d->precio_unitario));
                    $pagado = $p->pagos->sum('monto');
                    return $total > $pagado;
                })
                ->map(function ($p) {
                    $total    = $p->detalles->sum(fn($d) => $d->cantidad * floatval($d->precio_aplicado ?? $d->precio_unitario));
                    $pagado   = $p->pagos->sum('monto');
                    $pend     = max(0, $total - $pagado);
                    return [
                        'id'            => $p->id,
                        'numero_pedido' => $p->numero_pedido ?: 'N/A',
                        'fecha'         => $p->fecha ? Carbon::parse($p->fecha)->format('d/m/Y') : '-',
                        'ruta'          => $p->ruta->ruta ?? 'Sin ruta',
                        'usuario'       => $p->usuario->name ?? 'Desconocido',
                        'total'         => number_format($pend, 2, ',', '.'),
                        'total_raw'     => $pend,
                        'tipo_pago'     => $p->tipo_pago ?? 'N/A',
                    ];
                })
                ->values()
                ->toArray();

            $this->dispatch('abrir-modal-pedidos');
        } catch (\Throwable $e) {
            $this->pedidosSocio = [];
            $this->handleException($e, 'No se pudieron cargar los pedidos del socio.');
        }
    }

    public function cerrarPedidosModal(): void
    {
        try {
            $this->socioPedidosId = null;
            $this->pedidosSocio = [];
            $this->mostrarDetalleModal = false;
            $this->pedidoSeleccionado = null;
            $this->detallesPedido = [];
        } catch (\Throwable $e) {
            $this->handleException($e, 'No se pudo cerrar el modal de pedidos.');
        }
    }

    public function mostrarDetallePedido(int $pedidoId): void
    {
        try {
            if ($this->pedidoSeleccionado && $this->pedidoSeleccionado->id === $pedidoId) return;

            $this->pedidoSeleccionado = Pedido::with(['detalles.producto'])->find($pedidoId);

            if ($this->pedidoSeleccionado) {
                $this->detallesPedido = $this->pedidoSeleccionado->detalles->map(function ($d) {
                    $unit = floatval($d->precio_aplicado ?? $d->precio_unitario ?? 0);
                    return [
                        'producto'        => $d->producto->nombre ?? 'Producto desconocido',
                        'cantidad'        => $d->cantidad,
                        'precio_unitario' => number_format($unit, 2, ',', '.'),
                        'subtotal'        => number_format($unit * $d->cantidad, 2, ',', '.'),
                    ];
                })->toArray();

                $this->mostrarDetalleModal = true;
            }
        } catch (\Throwable $e) {
            $this->detallesPedido = [];
            $this->mostrarDetalleModal = false;
            $this->handleException($e, 'No se pudo mostrar el detalle del pedido.');
        }
    }

    /** ===== Cuentas contables ===== */
    public function abrirCuentasModal(int $socioId): void
    {
        try {
            $this->resetCuentasForm();
            $this->socioCuentasId = $socioId;

            $socio = SocioNegocio::with([
                'cuentas.cuentaCxc','cuentas.cuentaAnticipos','cuentas.cuentaDescuentos',
                'cuentas.cuentaRetFuente','cuentas.cuentaRetIca','cuentas.cuentaIva',
            ])->find($socioId);

            if ($socio && $socio->cuentas) {
                $this->cuenta_cxc_id        = $socio->cuentas->cuenta_cxc_id;
                $this->cuenta_anticipos_id  = $socio->cuentas->cuenta_anticipos_id;
                $this->cuenta_descuentos_id = $socio->cuentas->cuenta_descuentos_id;
                $this->cuenta_ret_fuente_id = $socio->cuentas->cuenta_ret_fuente_id;
                $this->cuenta_ret_ica_id    = $socio->cuentas->cuenta_ret_ica_id;
                $this->cuenta_iva_id        = $socio->cuentas->cuenta_iva_id;
            }

            $this->showCuentasModal = true;
        } catch (\Throwable $e) {
            $this->handleException($e, 'No se pudo abrir el modal de cuentas contables.');
        }
    }

    public function guardarCuentas(): void
    {
        try {
            $this->validate([
                'socioCuentasId'       => 'required|exists:socio_negocios,id',
                'cuenta_cxc_id'        => 'nullable|exists:plan_cuentas,id',
                'cuenta_anticipos_id'  => 'nullable|exists:plan_cuentas,id',
                'cuenta_descuentos_id' => 'nullable|exists:plan_cuentas,id',
                'cuenta_ret_fuente_id' => 'nullable|exists:plan_cuentas,id',
                'cuenta_ret_ica_id'    => 'nullable|exists:plan_cuentas,id',
                'cuenta_iva_id'        => 'nullable|exists:plan_cuentas,id',
            ]);

            DB::transaction(function () {
                SocioNegocioCuenta::updateOrCreate(
                    ['socio_negocio_id' => $this->socioCuentasId],
                    [
                        'cuenta_cxc_id'        => $this->cuenta_cxc_id,
                        'cuenta_anticipos_id'  => $this->cuenta_anticipos_id,
                        'cuenta_descuentos_id' => $this->cuenta_descuentos_id,
                        'cuenta_ret_fuente_id' => $this->cuenta_ret_fuente_id,
                        'cuenta_ret_ica_id'    => $this->cuenta_ret_ica_id,
                        'cuenta_iva_id'        => $this->cuenta_iva_id,
                    ]
                );
            });

            $this->loadListas();
            session()->flash('message', 'Cuentas contables asociadas correctamente.');
            $this->cerrarCuentasModal();
        } catch (ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $e) {
            $this->handleException($e, 'No se pudieron guardar las cuentas contables del socio.');
        }
    }

    public function cerrarCuentasModal(): void
    {
        try {
            $this->showCuentasModal = false;
            $this->resetCuentasForm();
        } catch (\Throwable $e) {
            $this->handleException($e, 'No se pudo cerrar el modal de cuentas.');
        }
    }

    private function resetCuentasForm(): void
    {
        try {
            $this->reset([
                'socioCuentasId',
                'cuenta_cxc_id',
                'cuenta_anticipos_id',
                'cuenta_descuentos_id',
                'cuenta_ret_fuente_id',
                'cuenta_ret_ica_id',
                'cuenta_iva_id',
            ]);
            $this->resetValidation();
        } catch (\Throwable $e) {
            $this->handleException($e, 'No se pudo limpiar el formulario de cuentas.');
        }
    }

    /** ===== Direcciones (modal hijo) ===== */
    public function abrirDirecciones(int $socioId): void
    {
        try {
            $this->socioDireccionesId = $socioId;
            $this->showDireccionesModal = true;
        } catch (\Throwable $e) {
            $this->handleException($e, 'No se pudo abrir el modal de direcciones.');
        }
    }

    public function cerrarDirecciones(): void
    {
        try {
            $this->socioDireccionesId = null;
            $this->showDireccionesModal = false;
        } catch (\Throwable $e) {
            $this->handleException($e, 'No se pudo cerrar el modal de direcciones.');
        }
    }

    /** ===== Condiciones de Pago (NUEVO) ===== */
    public function abrirCondicionesPago(int $socioId): void
    {
        try {
            $this->resetCondicionesForm();
            $this->socioCondicionesId = $socioId;

            $socio = SocioNegocio::find($socioId);
            if ($socio) {
                $this->condicion_pago       = $socio->condicion_pago ?: 'contado';
                $this->plazo_dias           = $socio->plazo_dias;
                $this->interes_mora_pct     = $socio->interes_mora_pct;
                $this->limite_credito       = $socio->limite_credito;
                $this->tolerancia_mora_dias = $socio->tolerancia_mora_dias;
                $this->dia_corte            = $socio->dia_corte;
            }

            $this->showCondicionesModal = true;
        } catch (\Throwable $e) {
            $this->handleException($e, 'No se pudieron abrir las condiciones de pago del socio.');
        }
    }

    public function guardarCondicionesPago(): void
    {
        try {
            $this->validate($this->condicionesRules());

            DB::transaction(function () {
                $socio = SocioNegocio::findOrFail($this->socioCondicionesId);

                $payload = [
                    'condicion_pago'       => $this->condicion_pago,
                    'plazo_dias'           => $this->condicion_pago === 'credito' ? $this->plazo_dias : null,
                    'interes_mora_pct'     => $this->condicion_pago === 'credito' ? $this->interes_mora_pct : null,
                    'limite_credito'       => $this->condicion_pago === 'credito' ? $this->limite_credito : null,
                    'tolerancia_mora_dias' => $this->condicion_pago === 'credito' ? $this->tolerancia_mora_dias : null,
                    'dia_corte'            => $this->condicion_pago === 'credito' ? $this->dia_corte : null,
                ];

                \Illuminate\Database\Eloquent\Model::unguarded(function () use ($socio, $payload) {
                    $socio->forceFill($payload)->save();
                });
            });

            $this->loadListas();
            session()->flash('message', 'Condiciones de pago guardadas correctamente.');
            $this->cerrarCondicionesPago();
        } catch (ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $e) {
            $this->handleException($e, 'No se pudieron guardar las condiciones de pago.');
        }
    }

    public function cerrarCondicionesPago(): void
    {
        try {
            $this->showCondicionesModal = false;
            $this->resetCondicionesForm();
        } catch (\Throwable $e) {
            $this->handleException($e, 'No se pudo cerrar el modal de condiciones de pago.');
        }
    }

    private function resetCondicionesForm(): void
    {
        try {
            $this->reset([
                'socioCondicionesId',
                'condicion_pago',
                'plazo_dias',
                'interes_mora_pct',
                'limite_credito',
                'tolerancia_mora_dias',
                'dia_corte',
            ]);
            // defaults
            $this->condicion_pago = 'contado';
        } catch (\Throwable $e) {
            $this->handleException($e, 'No se pudo limpiar el formulario de condiciones de pago.');
        }
    }

    private function condicionesRules(): array
    {
        // Validaciones condicionales para crédito
        $base = [
            'socioCondicionesId'   => 'required|exists:socio_negocios,id',
            'condicion_pago'       => 'required|in:contado,credito',
        ];

        if ($this->condicion_pago === 'credito') {
            $base = array_merge($base, [
                'plazo_dias'           => 'required|integer|min:1|max:365',
                'interes_mora_pct'     => 'required|numeric|min:0|max:100',
                'limite_credito'       => 'required|numeric|min:0',
                'tolerancia_mora_dias' => 'nullable|integer|min:0|max:60',
                'dia_corte'            => 'nullable|integer|min:1|max:31',
            ]);
        }

        return $base;
    }

    /** ===== Render ===== */
    public function render()
    {
        try {
            return view('livewire.socio-negocio.socio-negocios', [
                'municipiosOptions' => $this->municipiosOptions,
                'ciiuOptions'       => $this->ciiuOptions,
            ]);
        } catch (\Throwable $e) {
            $this->handleException($e, 'Ocurrió un error al renderizar la vista de socios.');
            return view('livewire.socio-negocio.socio-negocios', [
                'municipiosOptions' => [],
                'ciiuOptions'       => [],
            ]);
        }
    }

    /** ===== Helper centralizado para errores ===== */
    private function handleException(\Throwable $e, string $flashMsg = 'Ocurrió un error inesperado.'): void
    {
        Log::error($flashMsg.' '.$e->getMessage(), [
            'exception' => get_class($e),
            'trace'     => $e->getTraceAsString(),
            'user_id'   => Auth::id(),
        ]);

        session()->flash('error', $flashMsg);
    }
}
