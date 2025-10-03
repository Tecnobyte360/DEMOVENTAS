<?php

namespace App\Livewire\OperacionesStock;

use Livewire\Component;

class OperacionesStock extends Component
{
    public $tab = 'entradas'; // 🔥 Inicializamos la pestaña activa por defecto

    public function render()
    {
        return view('livewire.operaciones-stock.operaciones-stock');
    }
}
