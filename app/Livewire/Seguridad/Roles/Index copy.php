<?php

namespace App\Livewire\Seguridad\Roles;

use Spatie\Permission\Models\Role;
use Livewire\Component;
use Livewire\WithPagination; // Importar la paginación

class Index extends Component
{
    use WithPagination;

    public $showModal = false;  
    public $selectedRoleId = null; // Agregar esta variable para manejar el rol seleccionado

    protected $listeners = ['roleAdded' => 'refreshRoles', 'closeModal' => 'closeModal'];

    public function openModal()
    {
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->dispatch('close-modal');
    }

    public function editRole($roleId)
    {
        $this->selectedRoleId = $roleId;
        $this->dispatch('openEditModal');
    }

    public function refreshRoles()
    {
        $this->resetPage(); 
    }

    public function deleteRole($roleId)
    {
        $role = Role::find($roleId);
        if ($role) {
            $role->delete();
            session()->flash('message', 'Rol eliminado correctamente.');
            $this->resetPage();
        }
    }

    public function render()
    {
        return view('livewire.seguridad.roles.index', [
            'roles' => Role::paginate(10),
            'selectedRoleId' => $this->selectedRoleId 
        ]);
    }
    
    
}
