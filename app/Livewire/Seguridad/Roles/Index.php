<?php

namespace App\Livewire\Seguridad\Roles;

use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\Validate;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Livewire\Traits\AdapterLivewireExceptionTrait;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use App\Livewire\Traits\AdapterValidateLivewireInputTrait;

#[Isolate]
class Index extends Component
{
    use 
        // AdapterLivewireExceptionTrait,
        // AdapterValidateLivewireInputTrait,
        WithPagination,
        WithFileUploads;
    
    public $search = '';

    public $isVisibleCreatePermissionModal = false;
    public $isVisibleEditPermissionModal = false;

    #[On('CloseModalClick')]
    public function CloseModalClick($modal_to_close){

        if (isset($this) && isset($modal_to_close)) {
            $this->$modal_to_close = false;
            if($modal_to_close == 'isVisibleCreatePermissionModal'){
                $this->reset(['name', 'description']);
            }
        }
    }

    #[Validate('required', message: 'El nombre del permiso es obligatorio')]
    public $name = '';
    
    #[Validate('required', message: 'La descripción del permiso es obligatorio')]
    public $description = '';

    public function store()
    {
         $user = Permission::where('name', $this->name)->first();
        if ($user) {
            $variables_to_validate = ['name'];
            $name_aux = '';
            try{
                $name_aux = $this->name;
                $this->reset(['name']);
            } catch (\Exception $e) {
                $this->js('alert("El permiso: ' .$name_aux. ' ya se encuentra registrado")');
                $this->name = $name_aux;
                $this->addError('name', 'IGNORE');
                $this->dispatch('EscapeEnabled');
                return;
            }
        }

        $variables_to_validate = ['name', 'description'];

        $this->validate([ 
            'name' => 'required',
            'description' => 'required',
        ]);

        Permission::create([
            'name' => $this->name,
            'description' => $this->description,
            'guard_name' => 'web'
        ]);
    
        // Toaster::info('Registro creado');
        $this->dispatch('EscapeEnabled');
        $this->dispatch('CloseModalClick', 'isVisibleCreatePermissionModal');

    }


    public function OpenCreatePermission(){
        $this->reset(['name', 'description']);
        $this->isVisibleCreatePermissionModal = true;
    }

    #[On('SetRefreshIndexPermissionComponent')]
    public function SetRefreshIndexPermissionComponent(){
        $this->dispatch('$refresh');
    }

    
    public $permissionId;

    public $aux_name;

    public function OpenEditPermission($permission_id){
        $this->permissionId = $permission_id;
        $permission = Permission::where('id', $this->permissionId)->first();
        $this->name = $permission->name;
        $this->aux_name = $this->name;
        $this->description = $permission->description;
        $this->isVisibleEditPermissionModal = true;
    }

    public function OpenDeletePermissionDichotomic($permission_id){

        \DB::table('permissions')->where('id', $permission_id)->delete();

    }

    
    public function update()
    {
        $rol = Permission::where('name', $this->name)->first();
        if ($rol && ($this->aux_name != $this->name)) {
            $variables_to_validate = ['name'];
            $name_aux = '';
            try{
                $name_aux = $this->name;
                $this->reset(['name']);
            } catch (\Exception $e) {
                $this->js('alert("El permiso: ' .$name_aux. ' ya se encuentra registrado")');
                $this->name = $name_aux;
                $this->addError('name', 'IGNORE');
                $this->dispatch('EscapeEnabled');
                return;
            }
        }

        $variables_to_validate = ['name', 'description'];
    
        $this->validate([ 
            'name' => 'required',
            'description' => 'required',
        ]);

        $permission = Permission::find($this->permissionId);
        $permission->update([
            'name' => $this->name,
            'description' => $this->description,
            'guard_name' => 'web'
        ]);
        // Toaster::info('Permiso editado');
        $this->dispatch('SetRefreshIndexPermissionComponent');
        $this->dispatch('EscapeEnabled');
        $this->dispatch('CloseModalClick', 'isVisibleEditPermissionModal');
    }


    public function render()
    {
        $permissions = Permission::with('roles')
            ->where('name', 'like', '%' . $this->search . '%')
            ->orderBy('id', 'desc')
            ->paginate(10);

        $users = User::with(['permissions', 'roles'])
            ->orderBy('name')
            ->paginate(10);

        $roles = Role::orderBy('name')->get();


        return view('livewire.seguridad.roles.index', [
            'permissions' => $permissions,
            'users' => $users,
            'roles' => $roles
        ]);
    }
}