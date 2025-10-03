<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataFeedController;
use App\Http\Controllers\DashboardController;

use App\Http\Controllers\OrderController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\FacturaPosPrintController;
use App\Http\Controllers\FacturaTicketController;
use App\Http\Controllers\Usuarios\UsuariosController;
use App\Livewire\Bodegas\Bodega;
use App\Livewire\Bodegas\Edit;
use App\Livewire\Catalogos\CiiuActividades;
use App\Livewire\Catalogos\Municipios;
use App\Livewire\Categoria\Categorias;
use App\Livewire\Categorias\IndexCategorias;
use App\Livewire\CondicionesPago\Condicionpago;
use App\Livewire\CondicionPagos\CondicionesPagos;
use App\Livewire\ConfiguracionEmpresas\Empresas;
use App\Livewire\Contabilidad\Asientos;
use App\Livewire\Cotizaciones\Cotizacion;
use App\Livewire\CuentasContables\PlanCuentas;
use App\Livewire\Facturas\FacturaForm;
use App\Livewire\Facturas\Index;
use App\Livewire\Facturas\NotaCreditoForm;
use App\Livewire\Finanzas\Finanzas;
use App\Livewire\Finanzas\GastosEmpresa;
use App\Livewire\Finanzas\TiposGasto;
use App\Livewire\Impuesto\Impuesto;
use App\Livewire\Inventario\DevolucionesMercancia;
use App\Livewire\Inventario\EntradasMercancia;
use App\Livewire\Inventario\Salidas;
use App\Livewire\MaestroRutas\MaestroRutas;
use App\Livewire\MediosPagos\MediosPagos;
use App\Livewire\NormasReparto\NormasReparto;
use App\Livewire\OperacionesStock\OperacionesStock;
use App\Livewire\Productos\Productos;
use App\Livewire\RutaDisponiblesConductor\RutasDisponiblesConductor;
use App\Livewire\Usuarios\Usuarios;
use App\Livewire\Seguridad\Roles\Index as RolesIndex;
use App\Livewire\Seguridad\Roles\Create as RolesCreate;
use App\Livewire\Seguridad\Roles\Edit as RolesEdit;
use App\Livewire\Seguridad\Roles\IndexP;
use App\Livewire\Serie\Serie;
use App\Livewire\SocioNegocio\SocioNegocios;
use App\Livewire\SubCategorias\SubCategorias;
use App\Livewire\Vehiculos\Vehiculo;
use App\Models\cotizaciones\cotizacione;
use App\Models\Devoluciones\Devolucion;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::redirect('/', 'login');

Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    // Route for the getting the data feed
    Route::get('/json-data-feed', [DataFeedController::class, 'getDataFeed'])->name('json_data_feed');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/analytics', [DashboardController::class, 'analytics'])->name('analytics');
    Route::get('/dashboard/fintech', [DashboardController::class, 'fintech'])->name('fintech');

    Route::get('/ecommerce/orders', [OrderController::class, 'index'])->name('orders');
    Route::get('/ecommerce/invoices', [InvoiceController::class, 'index'])->name('invoices');

    Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns');
    Route::get('/community/users-tabs', [MemberController::class, 'indexTabs'])->name('users-tabs');
    Route::get('/community/users-tiles', [MemberController::class, 'indexTiles'])->name('users-tiles');
    Route::get('/community/profile', function () {
        return view('pages/community/profile');
    })->name('profile');
    Route::get('/community/feed', function () {
        return view('pages/community/feed');
    })->name('feed');
    Route::get('/community/forum', function () {
        return view('pages/community/forum');
    })->name('forum');
    Route::get('/community/forum-post', function () {
        return view('pages/community/forum-post');
    })->name('forum-post');
    Route::get('/community/meetups', function () {
        return view('pages/community/meetups');
    })->name('meetups');
    Route::get('/community/meetups-post', function () {
        return view('pages/community/meetups-post');
    })->name('meetups-post');
    Route::get('/finance/cards', function () {
        return view('pages/finance/credit-cards');
    })->name('credit-cards');
    Route::get('/finance/transactions', [TransactionController::class, 'index01'])->name('transactions');
    Route::get('/finance/transaction-details', [TransactionController::class, 'index02'])->name('transaction-details');
    Route::get('/job/job-listing', [JobController::class, 'index'])->name('job-listing');
    Route::get('/job/job-post', function () {
        return view('pages/job/job-post');
    })->name('job-post');
    Route::get('/job/company-profile', function () {
        return view('pages/job/company-profile');
    })->name('company-profile');
    Route::get('/messages', function () {
        return view('pages/messages');
    })->name('messages');
    Route::get('/tasks/kanban', function () {
        return view('pages/tasks/tasks-kanban');
    })->name('tasks-kanban');
    Route::get('/tasks/list', function () {
        return view('pages/tasks/tasks-list');
    })->name('tasks-list');
    Route::get('/inbox', function () {
        return view('pages/inbox');
    })->name('inbox');
    Route::get('/calendar', function () {
        return view('pages/calendar');
    })->name('calendar');
    Route::get('/settings/account', function () {
        return view('pages/settings/account');
    })->name('account');
    Route::get('/settings/notifications', function () {
        return view('pages/settings/notifications');
    })->name('notifications');
    Route::get('/settings/apps', function () {
        return view('pages/settings/apps');
    })->name('apps');
    Route::get('/settings/plans', function () {
        return view('pages/settings/plans');
    })->name('plans');
    Route::get('/settings/billing', function () {
        return view('pages/settings/billing');
    })->name('billing');
    Route::get('/settings/feedback', function () {
        return view('pages/settings/feedback');
    })->name('feedback');
    Route::get('/utility/changelog', function () {
        return view('pages/utility/changelog');
    })->name('changelog');
    Route::get('/utility/roadmap', function () {
        return view('pages/utility/roadmap');
    })->name('roadmap');
    Route::get('/utility/faqs', function () {
        return view('pages/utility/faqs');
    })->name('faqs');
    Route::get('/utility/empty-state', function () {
        return view('pages/utility/empty-state');
    })->name('empty-state');
    Route::get('/utility/404', function () {
        return view('pages/utility/404');
    })->name('404');
    Route::get('/utility/knowledge-base', function () {
        return view('pages/utility/knowledge-base');
    })->name('knowledge-base');
    Route::get('/onboarding-01', function () {
        return view('pages/onboarding-01');
    })->name('onboarding-01');
    Route::get('/onboarding-02', function () {
        return view('pages/onboarding-02');
    })->name('onboarding-02');
    Route::get('/onboarding-03', function () {
        return view('pages/onboarding-03');
    })->name('onboarding-03');
    Route::get('/onboarding-04', function () {
        return view('pages/onboarding-04');
    })->name('onboarding-04');
    Route::get('/component/button', function () {
        return view('pages/component/button-page');
    })->name('button-page');
    Route::get('/component/form', function () {
        return view('pages/component/form-page');
    })->name('form-page');
    Route::get('/component/dropdown', function () {
        return view('pages/component/dropdown-page');
    })->name('dropdown-page');
    Route::get('/component/alert', function () {
        return view('pages/component/alert-page');
    })->name('alert-page');
    Route::get('/component/modal', function () {
        return view('pages/component/modal-page');
    })->name('modal-page');
    Route::get('/component/pagination', function () {
        return view('pages/component/pagination-page');
    })->name('pagination-page');
    Route::get('/component/tabs', function () {
        return view('pages/component/tabs-page');
    })->name('tabs-page');
    Route::get('/component/breadcrumb', function () {
        return view('pages/component/breadcrumb-page');
    })->name('breadcrumb-page');
    Route::get('/component/badge', function () {
        return view('pages/component/badge-page');
    })->name('badge-page');
    Route::get('/component/avatar', function () {
        return view('pages/component/avatar-page');
    })->name('avatar-page');
    Route::get('/component/tooltip', function () {
        return view('pages/component/tooltip-page');
    })->name('tooltip-page');
    Route::get('/component/accordion', function () {
        return view('pages/component/accordion-page');
    })->name('accordion-page');
    Route::get('/component/icons', function () {
        return view('pages/component/icons-page');
    })->name('icons-page');
    Route::fallback(function () {
        return view('pages/utility/404');
    });



    Route::get('/Usuarios', Usuarios::class)->name('Usuarios');
    Route::middleware(['auth'])->group(function () {

        Route::get('/roles', IndexP::class)->name('roles.index');
        Route::get('/permisos', RolesIndex::class)->name('roles.index2');
        Route::get('/roles/crear', RolesCreate::class)->name('roles.create');
        Route::get('/roles/editar/{id}', RolesEdit::class)->name('roles.edit');
    });

    Route::get('/Bodegas', Bodega::class)->name('Bodegas');
    Route::get('/SociosNegocio', SocioNegocios::class)->name('SociosNegocio');
    Route::get('/socio-negocio/{id}/edit', \App\Livewire\SocioNegocio\Edit::class)->name('socio-negocio.edit');
    Route::get('/productos', Productos::class)->name('productos.index');
    Route::get('/categorias', Categorias::class)->name('categorias.index');
    Route::get('/subcategorias', SubCategorias::class)->name('subcategorias.index');
    Route::get('/indexcategorias', IndexCategorias::class)->name('indexcategorias');


    //Inventario

    Route::get('/EntradasMercancia', EntradasMercancia::class)->name('entradas.mercancia');
    Route::get('/Operaciones-stock', OperacionesStock::class)->name('Operaciones-stock');
    Route::get('/Maestro-Rutas', MaestroRutas::class)->name('Maestro-Rutas');
    Route::get('/Vehiculos', Vehiculo::class)->name('Vehiculos');
    Route::get('/RutasDisponibles', RutasDisponiblesConductor::class)->name('RutasDisponibles');
    Route::get('/DevolucionMercancia', DevolucionesMercancia::class)->name('DevolucionMercancia');
    Route::get('/SalidaMercancia', Salidas::class)->name('SalidaMercancia');
    //finanzas
    Route::get('/Finanzas', Finanzas::class)->name('Finanzas');
    Route::get('/Gastos', GastosEmpresa::class)->name('Gastos');
    Route::get('/tiposGastos', TiposGasto::class)->name('tiposGastos');
    Route::get('/Empresas', Empresas::class)->name('Empresas');

    //cotizaciones
    Route::get('/Cotizaciones', Cotizacion::class)->name('Cotizaciones');
    Route::get('/Facturacion', Index::class)->name('Facturacion');
    Route::get('/Notas-credito-clientes', NotaCreditoForm::class)->name('notascreditoclientes');
    Route::get('/SeriesDocumentos', Serie::class)->name('SeriesDocumentos');
    Route::get('/facturas/{factura}/ticket', [FacturaTicketController::class, 'show'])
        ->whereNumber('factura')
        ->name('facturas.ticket');
    Route::post('/facturas/{factura}/print-pos', [FacturaPosPrintController::class, 'print'])
        ->name('facturas.print-pos');
    Route::get('/normas-reparto', NormasReparto::class)->name('normas-reparto.index');
    Route::get('/Cuentas-contables', PlanCuentas::class)->name('Cuentas-contables');
    Route::get('/Impuestos', Impuesto::class)->name('Impuestos');
//     Route::get('/catalogos/municipios', Municiopios::class)->name('catalogos/municipios');
// Route::get('/catalogos/ciiu', CiiuActividades::class)
//     ->name('catalogos.ciiu');

    Route::get('/contabilidad/asientos', Asientos::class)
        ->name('asientos.index');
      Route::get('/condiciones-pago', CondicionesPagos::class)
        ->name('condicionespago');

        Route::get('/facturas/{id}/editar', \App\Livewire\Facturas\FacturaForm::class)
    ->name('facturas.edit');

          Route::get('/medios-pagos', MediosPagos::class)
        ->name('Mediospagos');

});
