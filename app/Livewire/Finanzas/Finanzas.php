<?php

namespace App\Livewire\Finanzas;

use Livewire\Component;

class Finanzas extends Component
{
    public $tab = 'creditos'; // Tab por defecto

    public function render()
    {
        return view('livewire.finanzas.finanzas');
    }
}
