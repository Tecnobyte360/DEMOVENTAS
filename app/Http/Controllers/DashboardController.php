<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Models\DataFeed;

class DashboardController extends Controller
{
    /**
     * Mapa permiso → ruta de aterrizaje cuando el usuario no puede ver el dashboard.
     * El primer permiso que tenga el usuario define a dónde lo enviamos.
     */
    private array $aterrizajePorPermiso = [
        'pos.ver'             => 'pos.factura',
        'facturas.ver'        => 'Facturacion',
        'ventas.ver'          => 'Facturacion',
        'cotizaciones.ver'    => 'Cotizaciones',
        'pagos.ver'           => 'PagosRecibidos',
        'caja.ver'            => 'abrircaja',
        'compras.ver'         => 'IndexFacturas',
        'inventario.ver'      => 'inventario.por.bodega',
        'productos.ver'       => 'productos',
        'terceros.ver'        => 'socios.negocio',
        'finanzas.ver'        => 'reportes.ventas',
    ];

    public function index()
    {
        $user = Auth::user();

        // Solo aplica el gate si el permiso ya existe en la BD (back-compat: si nunca se ha creado, dashboard sigue abierto)
        $permisoExiste = \Spatie\Permission\Models\Permission::where('name', 'dashboard.ver')->exists();

        if ($user && $permisoExiste && !$user->can('dashboard.ver')) {
            foreach ($this->aterrizajePorPermiso as $perm => $routeName) {
                if ($user->can($perm) && Route::has($routeName)) {
                    return redirect()->route($routeName);
                }
            }
            abort(403, 'No tienes permiso para acceder al panel.');
        }

        $dataFeed = new DataFeed();

        return view('pages/dashboard/dashboard', compact('dataFeed'));
    }

    /**
     * Displays the analytics screen
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function analytics()
    {
        return view('pages/dashboard/analytics');
    }

    /**
     * Displays the fintech screen
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function fintech()
    {
        return view('pages/dashboard/fintech');
    }
}
