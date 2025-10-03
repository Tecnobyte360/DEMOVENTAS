<?php

namespace App\Livewire\Usuarios;

use App\Models\User;
use App\Models\Role;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class Usuarios extends Component
{
    public $usuarios;
    public $mensaje;
    public $tipoMensaje;

    public $isVisibleEditUserModal = false;
    public $isVisibleChangePassUserModal = false;
    public $isVisibleCreateUserModal = false;

    // protected $listeners = [
    //     'usuario-creado' => 'mostrarMensajeExito',
    //     'usuario-error' => 'mostrarMensajeError',
    //     'actualizarUsuarios' => 'cargarUsuarios',
    // ];

    #[On('actualizarUsuarios')]
    public function refreshcomponent(){
        $this->dispatch('$refresh');
    }

    public function mount()
    {
        $this->cargarUsuarios();
    }

    public function cargarUsuarios()
    {
        $this->usuarios = User::all();
    }

    public $availableRoles = [];
    public $selectedRole = '';

    public function boot(){
        $this->availableRoles = Role::all();
    }

    public $name;
    public $email;
    public $user_id;

    public function editarUsuario($id){
        $this->user_id = $id;
        $user_rol = User::with('roles')->find($id);
        if ($user_rol && $user_rol->roles->isNotEmpty()) {
            $this->selectedRole = $user_rol->roles->first()->id;
        }

        $user_data = User::where('id', $id)->first();
        $this->name = $user_data->name;
        $this->email = $user_data->email;

        $this->isVisibleEditUserModal = true;
    }

    public $rolePermissions = [];

    public function loadRolePermissions($roleId)
    {
        if ($roleId) {
            $role = Role::findOrFail($roleId);
            $this->rolePermissions = $role->permissions->pluck('name')->toArray();
        } else {
            $this->rolePermissions = [];
        }
    }

    public $password;

    public function openNewUser(){
        $this->reset(['name', 'email', 'password', 'selectedRole']);
        $this->isVisibleCreateUserModal = true;
    }

    public function saveEditUsuario()
    {
        // Validar datos de entrada
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'selectedRole' => 'required|exists:roles,id',
        ]);

        try {
            // Buscar el usuario
            $user = User::findOrFail($this->user_id);
            
            // Actualizar datos básicos
            $user->name = $this->name;
            $user->email = $this->email;
            $user->save();
            
            $this->selectedRole = intval($this->selectedRole);
            
            // Sincronizar rol (elimina todos los roles anteriores y asigna el nuevo)
            $user->syncRoles([$this->selectedRole]);
            
            // Cerrar modal y mostrar mensaje de éxito
            $this->isVisibleEditUserModal = false;
            $this->reset(['name', 'email', 'selectedRole', 'user_id']);

            $this->js('window.location.reload();');
            
            // Mensaje de éxito (ajústalo según el sistema de notificaciones que uses)
            session()->flash('message', 'Usuario actualizado correctamente.');
            
        } catch (\Exception $e) {
            // Manejo de errores
            session()->flash('error', 'Error al actualizar usuario: ' . $e->getMessage());
        }
    }
    

    public function changepassworduser($id){
        $this->user_id = $id;
        $this->reset(['nuevaPassword', 'nuevaPassword_confirmation']);
        $this->isVisibleChangePassUserModal = true;
    }

    public $nuevaPassword = '';
    public $nuevaPassword_confirmation = '';

    protected $listeners = ['mostrarFormularioContrasena' => 'abrirModal'];

    protected $rules = [
        'nuevaPassword' => 'required|string|min:8|confirmed',
    ];

    protected $messages = [
        'nuevaPassword.confirmed' => 'La confirmación de la contraseña no coincide.',
        'nuevaPassword.min' => 'La contraseña debe tener al menos 8 caracteres.',
    ];


    public function cambiarpass()
    {
        $this->validate();

        $usuario = User::findOrFail($this->user_id);
        $usuario->password = Hash::make($this->nuevaPassword);
        $usuario->save();

        session()->flash('mensaje', 'Contraseña actualizada correctamente.');
        $this->isVisibleChangePassUserModal = false;
    }

        public function guardarUsuario()
    {
        try {
            $this->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'password' => 'required|min:6|max:255',
                'selectedRole' => 'required|exists:roles,id',
            ]);

            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'activo' => true,
            ]);


            if ($this->selectedRole) {
                $user->assignRole(intval($this->selectedRole));
                $this->dispatch('SetRefreshSidebarComponent');
            }
            $this->isVisibleCreateUserModal = false;
            $this->dispatch('$refresh');
            // Emitir eventos para cerrar modal y actualizar tabla
            $this->dispatch('usuario-creado', ['mensaje' => '¡Usuario registrado exitosamente!']);
            $this->dispatch('actualizarUsuarios');

        } catch (\Exception $e) {
            $this->dispatch('usuario-error', ['mensaje' => 'Error al registrar el usuario.']);
        }
    }

    public function toggleEstado($id)
    {
        $usuario = User::findOrFail($id);
        $usuario->activo = !$usuario->activo;
        $usuario->save();
        $this->cargarUsuarios();
    }

    public function mostrarMensajeExito($data)
    {
        $this->mensaje = $data['mensaje'];
        $this->tipoMensaje = 'exito';
    }

    public function mostrarMensajeError($data)
    {
        $this->mensaje = $data['mensaje'];
        $this->tipoMensaje = 'error';
    }

    public function render()
    {
        return view('livewire.usuarios.usuarios');
    }
}
