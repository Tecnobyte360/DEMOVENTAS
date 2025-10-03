<?php

namespace App\Livewire\SocioNegocio;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SocioNegocioPreviewImport;
use Illuminate\Support\Collection;

class ImportarExcel extends Component
{
    use WithFileUploads;

    public $excelFile;
    public $previewData = [];

    protected $rules = [
        'excelFile' => 'required|file|mimes:xlsx,xls|max:10240',
    ];

    public function updatedExcelFile()
    {
        $this->validateOnly('excelFile');
        $this->preview(); // Automáticamente mostrar vista previa al cargar
    }

    public function preview()
    {
        $this->validate();

        try {
            $this->previewData = Excel::toCollection(new SocioNegocioPreviewImport, $this->excelFile)[0]->toArray();
        } catch (\Exception $e) {
            session()->flash('error', 'Error al leer el archivo: ' . $e->getMessage());
        }
    }

    public function import()
    {
        $this->validate();

        $errores = [];

        try {
            Excel::import(new \App\Imports\SocioNegocioImport($errores), $this->excelFile);

            if (count($errores) > 0) {
                session()->flash('errores_importacion', $errores);
            } else {
                session()->flash('message', 'Socios de negocio importados correctamente.');
            }

            $this->dispatch('refreshList');
            $this->excelFile = null;
            $this->previewData = [];

        } catch (\Exception $e) {
            session()->flash('error', 'Error al importar: ' . $e->getMessage());
        }
    }

    public function resetForm()
{
    $this->reset(['excelFile', 'previewData']);
    $this->resetValidation(); // limpia errores de validación si hay
    session()->forget(['message', 'error', 'errores_importacion']); // limpia los flashes
}


    public function render()
    {
        return view('livewire.socio-negocio.importar-excel');
    }
}
