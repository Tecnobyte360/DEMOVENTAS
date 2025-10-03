<?php

namespace App\Livewire\Seguridad\Permissions;

use Livewire\Component;
use Spatie\Permission\Models\Role;
class Edit extends Component
{

    
    public $roleId;
    public $name;

    protected $rules = [
        'name' => 'required|min:3|unique:roles,name',
    ];

    public function mount($id)
    {
        $role = Role::findOrFail($id);
        $this->roleId = $role->id;
        $this->name = $role->name;
    }

    public function update()
    {
        $this->validate();

        $role = Role::findOrFail($this->roleId);
        $role->update(['name' => $this->name]);

        session()->flash('message', 'Rol actualizado exitosamente.');

        return redirect()->route('roles.index');
    }

    public function render()
    {
        return view('livewire.seguridad.permissions.edit');
    }
}
