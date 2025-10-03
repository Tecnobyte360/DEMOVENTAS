<?php

namespace App\Livewire\Productos;

use App\Models\Productos\Producto;
use App\Models\Productos\PrecioProducto;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;
use Masmerise\Toaster\PendingToast;

class PreciosProducto extends Component
{
    /** 
     * Colección de todos los productos con sus precios (eager‐loading). 
     * @var Collection|Producto[]
     */
    public Collection $productos;

    /**
     * Término de búsqueda “activo” que se usará para filtrar.
     * @var string
     */
    public string $search = '';

    /**
     * Campo de texto donde el usuario escribe, antes de presionar “Buscar”.
     * @var string
     */
    public string $searchTerm = '';

    /**
     * Para cada producto, el “nombre” que el usuario ingrese de la nueva lista de precios.
     * La clave del array es el ID del producto.
     * @var array<string,string>
     */
    public array $nombres = [];

    /**
     * Para cada producto, el “valor” que el usuario ingrese de la nueva lista de precios.
     * La clave del array es el ID del producto.
     * @var array<string,string>
     */
    public array $valores = [];

    /**
     * Array que marca qué precio está en “modo edición”.
     * La clave es el ID del precio (PrecioProducto->id), y el valor booleano indica
     * si esa fila se está editando.
     *
     * @var array<int,bool>
     */
    public array $editingPrecio = [];

    /**
     * Para cada precio en edición, guardamos temporalmente el “nombre” nuevo.
     * La clave es el ID del precio (PrecioProducto->id).
     * @var array<int,string>
     */
    public array $editNombres = [];

    /**
     * Para cada precio en edición, guardamos temporalmente el “valor” nuevo.
     * La clave es el ID del precio (PrecioProducto->id).
     * @var array<int,string>
     */
    public array $editValores = [];

   public function mount()
{
    $this->productos = Producto::with('precios')
        ->orderBy('nombre')
        ->get();

    // Inicializa tus arrays…
    foreach ($this->productos as $prod) {
        $this->nombres[$prod->id] = '';
        $this->valores[$prod->id] = '';
    }
}


    /**
     * Copia el valor de searchTerm en search para activar el filtrado.
     */
    public function buscar()
    {
        $this->search = $this->searchTerm;
    }

    /**
     * Computed property que devuelve sólo los productos cuyo nombre
     * contiene la cadena $this->search (case‐insensitive).
     * Livewire lo expone en la vista como $filteredProducts.
     *
     * @return \Illuminate\Support\Collection|Producto[]
     */
    public function getFilteredProductsProperty()
    {
        if (trim($this->search) === '') {
            // Si no hay término de búsqueda, devolvemos todos
            return $this->productos;
        }

        $criterio = mb_strtolower($this->search);

        return $this->productos
            ->filter(fn($producto) => mb_stripos($producto->nombre, $criterio) !== false)
            ->values();
    }

    /**
     * Entra en modo edición para el precio $precioId.
     * Carga nombre y valor existentes en editNombres/$editValores.
     *
     * @param int $precioId
     */
    public function editarPrecio(int $precioId)
    {
        $precio = PrecioProducto::find($precioId);
        if (! $precio) {
            return;
        }

        // Activamos el flag de edición para este precio
        $this->editingPrecio[$precioId] = true;

        // Cargamos temporalmente los valores actuales en los arrays editNombres/editValores
        $this->editNombres[$precioId] = $precio->nombre;
        $this->editValores[$precioId] = $precio->valor;
    }

    /**
     * Cancela la edición para el precio $precioId.
     * Desactiva el flag y borra los valores temporales.
     *
     * @param int $precioId
     */
    public function cancelarEdicion(int $precioId)
    {
        unset($this->editingPrecio[$precioId]);
        unset($this->editNombres[$precioId]);
        unset($this->editValores[$precioId]);
    }

    /**
     * Valida y actualiza el precio en BD. Sale del modo edición al finalizar.
     *
     * @param int $precioId
     */
    public function actualizarPrecio(int $precioId)
    {
        // 1) Validar que el precio siga existiendo
        $precio = PrecioProducto::find($precioId);
        if (! $precio) {
            PendingToast::create()
                ->error()
                ->message("No se encontró el precio a actualizar.")
                ->duration(5000);
            $this->cancelarEdicion($precioId);
            return;
        }

        // 2) Validar los inputs en editNombres[precioId] y editValores[precioId]
        $this->validate([
            "editNombres.{$precioId}" => 'required|string|max:100',
            "editValores.{$precioId}" => 'required|numeric|min:0.01',
        ], [
            "editNombres.{$precioId}.required" => 'El nombre de la lista no puede quedar vacío.',
            "editValores.{$precioId}.required" => 'El valor no puede quedar vacío.',
        ]);

        $nuevoNombre = trim($this->editNombres[$precioId]);
        $nuevoValor  = $this->editValores[$precioId];

        // 3) Evitar que haya otro registro con el mismo nombre para este mismo producto
        $existeDuplicado = PrecioProducto::where('producto_id', $precio->producto_id)
            ->where('id', '!=', $precioId)
            ->whereRaw('LOWER(nombre) = ?', [mb_strtolower($nuevoNombre)])
            ->exists();

        if ($existeDuplicado) {
            PendingToast::create()
                ->warning()
                ->message("Ya existe otra lista con ese nombre para este producto.")
                ->duration(5000);
            return;
        }

        // 4) Guardar los cambios
        $precio->update([
            'nombre' => $nuevoNombre,
            'valor'  => $nuevoValor,
        ]);

        // 5) Recargar la colección de precios para este producto
        $producto = $this->productos->firstWhere('id', $precio->producto_id);
        if ($producto) {
            $producto->precios = PrecioProducto::where('producto_id', $precio->producto_id)->get();
        }

        // 6) Salir del modo edición y limpiar valores temporales
        $this->cancelarEdicion($precioId);

        PendingToast::create()
            ->success()
            ->message("Precio actualizado correctamente.")
            ->duration(5000);
    }

    /**
     * Elimina el precio con ID $precioId y recarga los precios del producto correspondiente.
     *
     * @param int $precioId
     */
    public function eliminarPrecio(int $precioId)
    {
        $precio = PrecioProducto::find($precioId);
        if (! $precio) {
            return;
        }

        $productoId = $precio->producto_id;
        $precio->delete();

        $producto = $this->productos->firstWhere('id', $productoId);
        if ($producto) {
            $producto->precios = PrecioProducto::where('producto_id', $productoId)->get();
        }

        PendingToast::create()
            ->success()
            ->message("Precio eliminado correctamente.")
            ->duration(5000);
    }

    /**
     * Crea un nuevo precio para el producto indicado.
     *
     * @param int $productoId
     */
    public function guardarPrecio(int $productoId)
    {
        $this->validate([
            "nombres.{$productoId}" => 'required|string|max:100',
            "valores.{$productoId}" => 'required|numeric|min:0.01',
        ], [
            "nombres.{$productoId}.required" => 'Debes indicar un nombre para la lista de precios.',
            "valores.{$productoId}.required" => 'Debes indicar un valor numérico para esta lista de precios.',
        ]);

        $nombreNuevo = trim($this->nombres[$productoId]);
        $valorNuevo  = $this->valores[$productoId];

        // Evitar duplicados (insensible a mayúsculas)
        $existe = PrecioProducto::where('producto_id', $productoId)
            ->whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombreNuevo)])
            ->exists();

        if ($existe) {
            PendingToast::create()
                ->warning()
                ->message("Ya existe una lista de precios con ese nombre para este producto.")
                ->duration(5000);
            return;
        }

        PrecioProducto::create([
            'producto_id' => $productoId,
            'nombre'      => $nombreNuevo,
            'valor'       => $valorNuevo,
        ]);

        // Limpiar únicamente los campos de este producto
        $this->nombres[$productoId] = '';
        $this->valores[$productoId] = '';

        // Recargar sólo la colección 'precios' de ese producto
        $producto = $this->productos->firstWhere('id', $productoId);
        if ($producto) {
            $producto->precios = PrecioProducto::where('producto_id', $productoId)->get();
        }

        PendingToast::create()
            ->success()
            ->message("Precio agregado correctamente.")
            ->duration(5000);
    }

    public function render()
    {
        return view('livewire.productos.precios-producto', [
            'filteredProducts' => $this->filteredProducts,
        ]);
    }
}
