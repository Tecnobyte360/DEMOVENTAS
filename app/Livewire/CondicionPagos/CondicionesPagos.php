<?php

namespace App\Livewire\CondicionPagos;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\CondicionPago\CondicionPago;
use Masmerise\Toaster\PendingToast;
use Throwable;

class CondicionesPagos extends Component
{
    /** UI */
    public bool $showModal = false;
    public ?int $editingId = null;
    public ?int $confirmingDeleteId = null;

    /** Filtros */
    public array $filters = ['q' => '', 'tipo' => '', 'activo' => ''];

    /** Form */
    public array $form = [
        'nombre'               => '',
        'tipo'                 => 'contado',   // contado|credito
        'plazo_dias'           => null,        // req si tipo=credito
        'interes_mora_pct'     => null,
        'limite_credito'       => null,
        'tolerancia_mora_dias' => null,
        'dia_corte'            => null,
        'notas'                => null,
        'activo'               => true,
    ];

    /* ========================= REGLAS ========================= */
    protected function rules(): array
    {
        return [
            'form.nombre' => [
                'bail','required','string','min:3','max:120',
                // evita nombres duplicados (ignora el actual en edición)
                Rule::unique('condicion_pagos', 'nombre')->ignore($this->editingId),
            ],
            'form.tipo'                 => ['bail','required', Rule::in(['contado','credito'])],
            'form.plazo_dias'           => ['nullable','integer','min:1','max:365'],
            'form.interes_mora_pct'     => ['nullable','numeric','min:0','max:1000'],
            'form.limite_credito'       => ['nullable','numeric','min:0'],
            'form.tolerancia_mora_dias' => ['nullable','integer','min:0','max:60'],
            'form.dia_corte'            => ['nullable','integer','min:1','max:31'],
            'form.notas'                => ['nullable','string','max:2000'],
            'form.activo'               => ['boolean'],
        ];
    }

    /** Mensajes “humanos” */
    protected function messages(): array
    {
        return [
            'form.nombre.required'  => 'El nombre es obligatorio.',
            'form.nombre.min'       => 'El nombre debe tener al menos :min caracteres.',
            'form.nombre.max'       => 'El nombre no puede superar :max caracteres.',
            'form.nombre.unique'    => 'Ya existe una condición con este nombre.',
            'form.tipo.required'    => 'Debes seleccionar el tipo.',
            'form.tipo.in'          => 'Tipo inválido.',
            'form.plazo_dias.required' => 'El plazo en días es obligatorio para condiciones de crédito.',
            'form.plazo_dias.min'      => 'El plazo debe ser al menos :min día.',
            'form.plazo_dias.max'      => 'El plazo no puede superar :max días.',
            'form.interes_mora_pct.min' => 'El interés de mora no puede ser negativo.',
            'form.interes_mora_pct.max' => 'El interés de mora es demasiado alto.',
            'form.limite_credito.min'   => 'El límite de crédito no puede ser negativo.',
            'form.tolerancia_mora_dias.max' => 'La tolerancia de mora no puede superar :max días.',
            'form.dia_corte.min'     => 'El día de corte mínimo es :min.',
            'form.dia_corte.max'     => 'El día de corte máximo es :max.',
        ];
    }

    /** Etiquetas de atributos para errores */
    protected function validationAttributes(): array
    {
        return [
            'form.nombre'               => 'nombre',
            'form.tipo'                 => 'tipo',
            'form.plazo_dias'           => 'plazo (días)',
            'form.interes_mora_pct'     => 'interés de mora',
            'form.limite_credito'       => 'límite de crédito',
            'form.tolerancia_mora_dias' => 'tolerancia de mora',
            'form.dia_corte'            => 'día de corte',
            'form.notas'                => 'notas',
            'form.activo'               => 'estado',
        ];
    }

    /* =================== VALIDACIÓN DINÁMICA ================== */

    /** Si es “crédito”, exigir plazo; si es “contado”, limpiar campos de crédito. */
    protected function validateCredito(): void
    {
        if (($this->form['tipo'] ?? 'contado') === 'credito') {
            $this->validate([
                'form.plazo_dias' => ['required','integer','min:1','max:365'],
            ], $this->messages(), $this->validationAttributes());

            // Regla de negocio adicional: tolerancia <= plazo (si ambos están)
            if (!is_null($this->form['tolerancia_mora_dias']) && !is_null($this->form['plazo_dias'])) {
                if ((int)$this->form['tolerancia_mora_dias'] > (int)$this->form['plazo_dias']) {
                    throw ValidationException::withMessages([
                        'form.tolerancia_mora_dias' => 'La tolerancia de mora no puede ser mayor que el plazo.',
                    ]);
                }
            }
        } else {
            // Si es contado, dejar en null todos los campos de crédito
            foreach (['plazo_dias','interes_mora_pct','limite_credito','tolerancia_mora_dias','dia_corte'] as $k) {
                $this->form[$k] = null;
            }
        }
    }

    /** Validación en vivo por campo */
    public function updated($prop): void
    {
        // Trims rápidos
        if ($prop === 'form.nombre') {
            $this->form['nombre'] = trim((string) $this->form['nombre']);
        }

        // Normalizar números al vuelo
        if (in_array($prop, [
            'form.plazo_dias','form.interes_mora_pct','form.limite_credito','form.tolerancia_mora_dias','form.dia_corte'
        ], true)) {
            $this->normalizeNumbers();
        }

        // Reglas base por campo
        try {
            $this->validateOnly($prop, $this->rules(), $this->messages(), $this->validationAttributes());

            // Val. condicional al cambiar tipo o campos clave
            if (in_array($prop, ['form.tipo','form.plazo_dias','form.tolerancia_mora_dias'], true)) {
                $this->validateCredito();
            }
        } catch (Throwable $e) {
            // Livewire ya pinta los errores; no hacemos toast acá.
        }
    }

    /* ================== LÓGICA DE FORMULARIO ================== */

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'nombre'               => '',
            'tipo'                 => 'contado',
            'plazo_dias'           => null,
            'interes_mora_pct'     => null,
            'limite_credito'       => null,
            'tolerancia_mora_dias' => null,
            'dia_corte'            => null,
            'notas'                => null,
            'activo'               => true,
        ];
        $this->resetValidation();
    }

    public function crear(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editar(int $id): void
    {
        try {
            $c = CondicionPago::findOrFail($id);
            $this->editingId = $c->id;
            $this->form = [
                'nombre'               => (string) $c->nombre,
                'tipo'                 => (string) $c->tipo,
                'plazo_dias'           => $c->plazo_dias,
                'interes_mora_pct'     => $c->interes_mora_pct,
                'limite_credito'       => $c->limite_credito,
                'tolerancia_mora_dias' => $c->tolerancia_mora_dias,
                'dia_corte'            => $c->dia_corte,
                'notas'                => $c->notas,
                'activo'               => (bool) $c->activo,
            ];
            $this->showModal = true;
        } catch (Throwable $e) {
            Log::error('Editar condición fallo: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            PendingToast::create()->error()->message('No se pudo abrir la condición.')->duration(6000);
        }
    }

    /** Convierte “1.000.000,50” → 1000000.50, y vacíos → null */
    private function normalizeNumbers(): void
    {
        $numericKeys = ['plazo_dias','interes_mora_pct','limite_credito','tolerancia_mora_dias','dia_corte'];

        foreach ($numericKeys as $k) {
            $v = $this->form[$k] ?? null;
            if ($v === '' || $v === null) { $this->form[$k] = null; continue; }

            // Quitar separadores de miles y usar punto decimal
            $val = str_replace([' ', ' '], '', (string)$v);           // quita espacios (incl. no-break)
            $val = str_replace(['.', ','], ['', '.'], $val);          // "1.234,56" -> "1234.56"
            if (is_numeric($val)) {
                // Para enteros (plazo, tolerancia, dia_corte)
                if (in_array($k, ['plazo_dias','tolerancia_mora_dias','dia_corte'], true)) {
                    $this->form[$k] = (int) round((float)$val);
                } else {
                    $this->form[$k] = (float) $val;
                }
            }
        }
    }

    public function guardar(): void
    {
        try {
            $this->normalizeNumbers();
            $this->validateCredito();
            $this->validate($this->rules(), $this->messages(), $this->validationAttributes());

            DB::transaction(function () {
                // Sanitiza nombre (sin dobles espacios)
                $this->form['nombre'] = preg_replace('/\s+/', ' ', trim($this->form['nombre'] ?? ''));

                // Si es contado, garantizamos nulls en campos de crédito
                if (($this->form['tipo'] ?? 'contado') !== 'credito') {
                    foreach (['plazo_dias','interes_mora_pct','limite_credito','tolerancia_mora_dias','dia_corte'] as $k) {
                        $this->form[$k] = null;
                    }
                }

                CondicionPago::updateOrCreate(
                    ['id' => $this->editingId],
                    $this->form
                );
            });

            $this->showModal = false;
            PendingToast::create()->success()->message('Condición guardada.')->duration(3500);
            $this->dispatch('refrescar-condiciones');
        } catch (ValidationException $ve) {
            throw $ve; // Livewire mostrará los errores en el formulario
        } catch (Throwable $e) {
            Log::error('Guardar condición fallo: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            PendingToast::create()
                ->error()
                ->message('No se pudo guardar la condición.')
                ->duration(9000);
        }
    }

    public function confirmarEliminar(int $id): void
    {
        $this->confirmingDeleteId = $id;
    }

    public function eliminar(): void
    {
        try {
            if (!$this->confirmingDeleteId) return;
            DB::transaction(function () {
                CondicionPago::findOrFail($this->confirmingDeleteId)->delete();
            });
            $this->confirmingDeleteId = null;
            PendingToast::create()->info()->message('Condición eliminada.')->duration(3500);
        } catch (Throwable $e) {
            Log::error('Eliminar condición fallo: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            PendingToast::create()->error()->message('No se pudo eliminar.')->duration(6000);
        }
    }

    public function toggleActivo(int $id): void
    {
        try {
            $c = CondicionPago::findOrFail($id);
            $c->activo = !$c->activo;
            $c->save();
            PendingToast::create()->info()->message($c->activo ? 'Condición activada.' : 'Condición desactivada.')->duration(3000);
        } catch (Throwable $e) {
            Log::error('Toggle activo condición fallo: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            PendingToast::create()->error()->message('No se pudo cambiar el estado.')->duration(6000);
        }
    }

    public function render()
    {
        try {
            $q = CondicionPago::query();

            if (trim($this->filters['q']) !== '') {
                $term = '%'.trim($this->filters['q']).'%';
                $q->where(fn($qq)=>$qq->where('nombre','like',$term)->orWhere('notas','like',$term));
            }
            if (in_array($this->filters['tipo'], ['contado','credito'], true)) {
                $q->where('tipo', $this->filters['tipo']);
            }
            if ($this->filters['activo'] !== '' && $this->filters['activo'] !== null) {
                $q->where('activo', (bool)$this->filters['activo']);
            }

            $condiciones = $q->orderBy('tipo')
                ->orderByRaw('COALESCE(plazo_dias,0)')
                ->orderBy('nombre')
                ->get();
        } catch (Throwable $e) {
            Log::error('Cargar condiciones fallo: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            $condiciones = collect();
            PendingToast::create()->error()->message('No se pudieron cargar las condiciones.')->duration(7000);
        }

        return view('livewire.condicion-pagos.condiciones-pagos', compact('condiciones'));
    }
}
