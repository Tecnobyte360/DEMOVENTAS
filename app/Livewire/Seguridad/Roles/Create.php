<?php

namespace App\Livewire\Seguridad\Roles;

use Exception;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\ValidationException;

class Create extends Component
{
    public $name = '';

    protected $rules = [
        'name' => 'required|min:3|unique:roles,name',
    ];

    public function store()
    {
        try {
            $this->validate();
            Role::create(['name' => trim($this->name)]);
    
            session()->flash('message', 'Rol creado exitosamente.');
            
            // 🔹 Primero, limpiamos los datos del formulario
            $this->reset(); 
            
            // 🔹 Luego, emitimos el evento para cerrar el modal
            $this->dispatch('close-modal'); 
            
            // 🔹 Finalmente, refrescamos la lista de roles
            $this->emit('roleAdded');
    
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
        } catch (Exception $e) {
            session()->flash('error', 'Error al crear el rol: ' . $e->getMessage());
        }
    }
    
    
    

    public function updated($propertyName)
    {
        try {
            $this->validateOnly($propertyName);
            $this->resetErrorBag($propertyName); // Limpia el error del campo corregido
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors()); // Mantiene el formato adecuado
        }
    }

    public function getDisabledProperty()
    {
        return empty($this->name) || strlen(trim($this->name)) < 3 || Role::where('name', $this->name)->exists();
    }

    public function cancel()
{
    $this->dispatch('close-modal');
    $this->reset('name'); 
}

    public function render()
    {
        return view('livewire.seguridad.roles.create');
    }
}
