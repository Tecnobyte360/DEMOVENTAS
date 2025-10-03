<div>
    <h2 class="text-xl font-bold mb-4">Editar Rol</h2>
    <form wire:submit.prevent="update">
        <label>Nombre del Rol:</label>
        <input type="text" wire:model="name" class="border p-2 w-full">
        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded mt-2">Actualizar</button>
    </form>
</div>
