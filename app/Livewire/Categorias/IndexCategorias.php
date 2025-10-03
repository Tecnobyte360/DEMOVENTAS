<?php

namespace App\Livewire\Categorias;

use Livewire\Component;

class IndexCategorias extends Component

{

    public $tab = 'categorias'; 
    public function render()
    {
        
        return view('livewire.categorias.index-categorias');
    }
}
