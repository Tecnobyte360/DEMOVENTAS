<div>
    <h2 class="text-xl font-bold mb-4">Lista de Roles</h2>
    <a href="{{ route('roles.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded">Crear Rol</a>
    
    @if(session()->has('message'))
        <div class="bg-green-500 text-white p-2 my-2">{{ session('message') }}</div>
    @endif

    <table class="table-auto w-full mt-4">
        <thead>
            <tr>
                <th class="border px-4 py-2">ID</th>
                <th class="border px-4 py-2">Nombre</th>
                <th class="border px-4 py-2">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($roles as $role)
            <tr>
                <td class="border px-4 py-2">{{ $role->id }}</td>
                <td class="border px-4 py-2">{{ $role->name }}</td>
                <td class="border px-4 py-2">
                    <a href="{{ route('roles.edit', $role->id) }}" class="bg-yellow-500 text-white px-2 py-1 rounded">Editar</a>
                    <button wire:click="deleteRole({{ $role->id }})" class="bg-red-500 text-white px-2 py-1 rounded">Eliminar</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
