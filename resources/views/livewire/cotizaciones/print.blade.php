<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Imprimir cotización</title>

  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-white">

  @include('livewire.cotizaciones.cotizacion', ['cotizacion' => $cotizacion])

  <script>
    window.addEventListener('load', () => {
      window.focus();
      window.print();
    });
  </script>
</body>
</html>