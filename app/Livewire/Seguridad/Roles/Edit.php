<?php

namespace App\Livewire\Seguridad\Roles;

use Livewire\Component;
use Spatie\Permission\Models\Role;
class Edit extends Component
{

    public $roleId;
    public $name;

    public function mount($roleId)
    {
        $role = Role::find($roleId);
        if ($role) {
            $this->roleId = $role->id;
            $this->name = $role->name;
        }
    }

    public function update()
    {
        $role = Role::find($this->roleId);
        if ($role) {
            $role->name = $this->name;
            $role->save();

            $this->dispatch('closeModal'); // Cerrar el modal
            $this->emit('refreshRoles'); // Actualizar lista de roles
        }
    }

    public function render()
    {
        return view('livewire.seguridad.roles.edit');
    }
}
