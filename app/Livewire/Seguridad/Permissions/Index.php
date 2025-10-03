<?php

namespace App\Livewire\Seguridad\Permissions;

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Exception;

class Index extends Component
{
    public $roles;

    protected $listeners = ['roleCreated' => 'loadRoles'];

    public function loadRoles()
    {
        $this->roles = Role::all();
    }

    public function mount()
    {
        $this->loadRoles();
    }

    public function deleteRole($id)
    {
        Role::find($id)->delete();
        $this->loadRoles();
    }

    public function render()
    {
        return view('livewire.seguridad.roles.index', [
            'roles' => $this->roles,
        ]);
    }

    
}
