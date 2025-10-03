<?php

namespace App\Livewire\Seguridad\Roles;


use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\Validate;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Livewire\Traits\AdapterLivewireExceptionTrait;
use Illuminate\Support\Facades\DB; 

#[Isolate]
class IndexP extends Component
{
    use WithPagination;
    
    public $search = '';
    public $selectedPermissions = [];

    public $isVisibleCreateRolesModal = false;

    public $isVisibleEditRolesModal = false;

    public $isVisibleAssignPermissionModal = false;

    
    #[Validate('required', message: 'El nombre del rol es obligatorio')]
    public $name = '';
    
    #[Validate('required', message: 'La descripción del rol es obligatorio')]
    public $description = '';

    #[On('SetRefreshIndexRolesComponent')]
    public function SetRefreshIndexRolesComponent(){
        $this->dispatch('$refresh');
    }

    
    #[On('CloseModalClick')]
    public function CloseModalClick($modal_to_close){

        if (isset($this) && isset($modal_to_close)) {
            $this->$modal_to_close = false;
            if($modal_to_close == 'isVisibleCreateRolesModal'){
                $this->reset(['name', 'description']);
            }
        }
    }

    public function store()
    {
        $user = Role::where('name', $this->name)->first();
        if ($user) {
            $name_aux = '';
            $name_aux = $this->name;
            $this->reset(['name']);
            $this->js('alert("El rol: ' .$name_aux. ' ya se encuentra registrado")');
            $this->name = $name_aux;
            $this->addError('name', 'IGNORE');
            $this->dispatch('EscapeEnabled');
            return;
        }
       $variables_to_validate = ['name', 'description'];

        $this->validate([ 
            'name' => 'required',
            'description' => 'required',
        ]);

        Role::create([
            'name' => $this->name,
            'description' => $this->description,
            'guard_name' => 'web'
        ]);

        // Toaster::info('Rol creado');
        $this->dispatch('SetRefreshIndexRolesComponent');
        $this->dispatch('EscapeEnabled');
        $this->dispatch('CloseModalClick', 'isVisibleCreateRolesModal');
    }


    public function OpenCreateRolesModal(){
        $this->reset(['name', 'description']);
        $this->isVisibleCreateRolesModal = true;
    }

    public $roleId;

    public $aux_name;

    public function OpenEditRolesModal($permission_id){
        $this->roleId = $permission_id;
        $role = Role::where('id', $this->roleId)->first();
        $this->name = $role->name;
        $this->aux_name = $this->name;
        $this->description = $role->description;
        $this->isVisibleEditRolesModal = true;
    }

    
    public function update()
    {
        $rol = Role::where('name', $this->name)->first();
        if ($rol && ($this->aux_name != $this->name)) {
            $variables_to_validate = ['name'];
            $name_aux = '';
            $name_aux = $this->name;
            $this->reset(['name']);
            $this->js('alert("El permiso: ' .$name_aux. ' ya se encuentra registrado")');
            $this->name = $name_aux;
            $this->addError('name', 'IGNORE');
            $this->dispatch('EscapeEnabled');
            return;
        }

        $variables_to_validate = ['name', 'description'];
    
        $this->validate([ 
            'name' => 'required',
            'description' => 'required',
        ]);

        $role = Role::find($this->roleId);
        $role->update([
            'name' => $this->name,
            'description' => $this->description
        ]);

        // Toaster::info('Rol editado');
        $this->dispatch('SetRefreshIndexRolesComponent');
        $this->dispatch('EscapeEnabled');
        $this->dispatch('CloseModalClick', 'isVisibleEditRolesModal');

    }

public function OpenDeleteEditRolesModal($rol_id)
{
    DB::table('roles')->where('id', $rol_id)->delete();

    // Opcional: mostrar alerta y recargar tabla
    $this->dispatch('SetRefreshIndexRolesComponent');
    $this->dispatch('notify', ['type' => 'success', 'message' => 'Rol eliminado correctamente.']);
}


    // #[On('DichotomicToDeleteRolesIndexRoles')]
    // public function DeleteEditRolesConfirmation($dict)
    // {
    //     $roleId = $dict['rol_id'];

    //     $role = Role::find($roleId);
    //     $role->delete();

    //     $this->dispatch('MediatorSetModalFalse', 'isVisibleDichotomicAskingModal');
    //     $this->dispatch('EscapeEnabled');
        
    //     // Toaster::info('Rol eliminado correctamente!');
    // }

    public $permissions;
    public $selectedRole;

    public function OpenAssignPermissionToRolesModal(Role $role){
        $this->selectedPermissions = $role->permissions->pluck('id')->toArray();

        $dict = [
            'selectedRole' => $role,
            'selectedPermissions' => $this->selectedPermissions
        ];

        // $this->dispatch('MediatorMountAssignPermissionToRolesModal', $mediator_dict);
        $this->permissions = Permission::orderBy('name')->get();

        if(!(Permission::exists())){
            $this->js('alert("No hay permisos registrados para asignarlos al rol seleccionado.")');
            $this->isVisibleAssignPermissionModal = false;
            $this->dispatch('EscapeEnabled');
            return;
        }

        $this->roleId = $dict['selectedRole'];
        $this->selectedRole = $dict['selectedRole'];
        $this->selectedPermissions = $dict['selectedPermissions'];

        $role = Role::where('id', $this->roleId['id'])->first();
        $this->name = $role->name;
        $this->isVisibleAssignPermissionModal = true;
    }

    public function updateRolePermissions()
    {
        try {
            // Convert all selected permission IDs to integers
            $selectedPermissions = array_map('intval', $this->selectedPermissions);
            
            // Make sure we have a valid role
            if ($this->selectedRole) {
                $role = Role::findOrFail($this->roleId['id']);
                
                // Verify each permission exists before syncing
                $validPermissions = Permission::whereIn('id', $selectedPermissions)->pluck('id')->toArray();
                
                // Sync only valid permissions
                $role->syncPermissions($validPermissions);
                
                // Show success message
                $this->dispatch('notify', ['type' => 'success', 'message' => 'Permisos actualizados correctamente']);
            }
            
            // Close modal and refresh the component
            $this->dispatch('CloseModalClick', 'isVisibleAssignPermissionModal');
            $this->dispatch('EscapeEnabled');
            $this->dispatch('SetRefreshIndexRolesComponent');
        } catch (\Exception $e) {
            dd($e->getMessage());
            // Handle the error
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Error al actualizar permisos: ' . $e->getMessage()]);
        }
    }


    public function DuplicatRol(Role $role)
    {
        $newRole = Role::create([
            'name' => $role->name . '-duplicado',
            'description' => $role->description,
            'guard_name' => 'web'
        ]);

        $permissions = $role->permissions->pluck('id')->toArray();
        $newRole->syncPermissions($permissions);
        
        $this->SetRefreshIndexRolesComponent();
        $this->dispatch('EscapeEnabled');
        // Toaster::info('Rol duplicado');
    }

    public function render()
    {
        $roles = Role::with('permissions')
            ->where('name', 'like', '%' . $this->search . '%')
            ->orderBy('id', 'desc')
            ->paginate(10);

        $permissions = Permission::orderBy('name')->get();

        return view('livewire.seguridad.roles.index2', [
            'roles' => $roles,
            'permissions' => $permissions
        ]);
    }
    
}