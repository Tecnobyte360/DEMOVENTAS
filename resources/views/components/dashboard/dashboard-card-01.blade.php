
      @livewire('indicadores.indicadores', [
            'titulo' => 'Ventas Totales',
            'valor' => $dataFeed->sumDataSet(1, 1),
            'colorFondo' => 'bg-green-100 dark:bg-green-900',
            'colorTexto' => 'text-green-800 dark:text-green-300',
            'icono' => 'fas fa-dollar-sign'
        ])
    </div>
 