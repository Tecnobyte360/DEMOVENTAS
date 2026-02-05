<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'tecnobyte360') }}</title>

    <link rel="icon" type="image/png"
        href="https://imagenes.20minutos.es/files/image_990_556/uploads/imagenes/2020/10/15/todos-los-mensajes-y-llamadas-de-whatsapp-estan-cifrados-de-extremo-a-extremo.jpeg">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400..700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <script>
        if (localStorage.getItem('dark-mode') === 'false' || !('dark-mode' in localStorage)) {
            document.documentElement.classList.remove('dark');
            document.documentElement.style.colorScheme = 'light';
        } else {
            document.documentElement.classList.add('dark');
            document.documentElement.style.colorScheme = 'dark';
        }
    </script>
</head>

<body
    class="h-full overflow-hidden font-inter antialiased bg-white text-slate-700 dark:bg-slate-950 dark:text-slate-300">
    <main class="relative h-[100dvh] overflow-hidden isolation-isolate flex items-center">
        <!-- Fondo ULTRA WHITE + azul sutil (WOW) -->
        <div class="pointer-events-none absolute inset-0">
            <!-- halos azul MUY sutiles -->
            <div class="absolute -top-40 -left-40 h-[32rem] w-[32rem] rounded-full bg-[#132742]/[0.08] blur-[120px]">
            </div>
            <div class="absolute top-1/3 -right-40 h-[34rem] w-[34rem] rounded-full bg-[#132742]/[0.06] blur-[140px]">
            </div>

            <!-- grano minimal (premium) -->
            <div class="absolute inset-0 opacity-[0.035]"
                style="background-image: radial-gradient(rgba(19,39,66,.25) 0.5px, transparent 0);
                        background-size: 22px 22px;">
            </div>

            <!-- línea suave superior (casi imperceptible) -->
            <div
                class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-slate-200 to-transparent dark:via-white/10">
            </div>
        </div>

        <div class="relative mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="py-4 sm:py-6 lg:py-8">
                <!-- Card WOW: BLANCA, flotando -->
                <div
                    class="grid grid-cols-1 overflow-hidden rounded-[2.75rem]
                            bg-white dark:bg-white/5
                            border border-slate-200/70 dark:border-white/10
                            shadow-[0_40px_90px_-30px_rgba(15,23,42,.18)]
                            max-h-[calc(100dvh-2rem)] sm:max-h-[calc(100dvh-3rem)] lg:max-h-[calc(100dvh-4rem)]
                            lg:grid-cols-2">

                    <!-- Brand line (azul fino) -->
                    <div class="lg:col-span-2 h-[3px] bg-gradient-to-r from-[#132742] via-sky-400 to-transparent"></div>

                    <!-- Panel Izquierdo -->
                    <div class="relative flex items-center justify-center p-6 sm:p-10">
                        <!-- overlay blanco (ultra clean) -->
                        <div
                            class="pointer-events-none absolute inset-0 bg-gradient-to-b from-white via-white/95 to-white/80 dark:from-white/10 dark:via-white/5 dark:to-transparent">
                        </div>

                        <!-- borde interno ultra sutil -->
                        <div
                            class="pointer-events-none absolute inset-0 ring-1 ring-[#132742]/[0.06] dark:ring-white/10">
                        </div>

                        <!-- contenedor del slot -->
                        <div
                            class="relative w-full max-w-md overflow-y-auto
                                    max-h-[calc(100dvh-8rem)] sm:max-h-[calc(100dvh-9rem)] lg:max-h-[calc(100dvh-6rem)]
                                    pr-1">
                            <!-- “tinte” corporativo para elementos del slot -->
                            <div
                                class="
                                [&_h1]:text-[#132742] [&_h1]:font-extrabold
                                [&_h2]:text-[#132742] [&_h2]:font-bold
                                [&_a]:text-[#132742] [&_a:hover]:text-sky-600 [&_a:hover]:underline
                                dark:[&_a]:text-sky-300 dark:[&_a:hover]:text-white

                                [&_input]:bg-white [&_textarea]:bg-white [&_select]:bg-white
                                dark:[&_input]:bg-white/5 dark:[&_textarea]:bg-white/5 dark:[&_select]:bg-white/5

                                [&_input]:border-slate-200 [&_select]:border-slate-200 [&_textarea]:border-slate-200
                                dark:[&_input]:border-white/10 dark:[&_select]:border-white/10 dark:[&_textarea]:border-white/10

                                [&_input:focus]:ring-4 [&_input:focus]:ring-[#132742]/20 [&_input:focus]:border-[#132742]/40
                                [&_select:focus]:ring-4 [&_select:focus]:ring-[#132742]/20 [&_select:focus]:border-[#132742]/40
                                [&_textarea:focus]:ring-4 [&_textarea:focus]:ring-[#132742]/20 [&_textarea:focus]:border-[#132742]/40

                                [&_button.primary]:bg-[#132742] [&_button.primary]:text-white
                                [&_button.primary:hover]:bg-[#0f1f34]
                                [&_*]:break-words
                            ">
                                {{ $slot }}
                            </div>
                        </div>
                    </div>


                    <div class="relative hidden lg:flex items-center justify-center p-10">
                        <!-- degradado azul premium -->
                        <div class="absolute inset-0 bg-gradient-to-br from-[#0b1626] via-[#132742] to-[#09111d]"></div>

                        <!-- glow suave -->
                        <div class="absolute -top-32 -left-32 h-80 w-80 rounded-full bg-white/10 blur-[120px]"></div>
                        <div class="absolute -bottom-32 -right-32 h-96 w-96 rounded-full bg-sky-400/20 blur-[140px]">
                        </div>

                        <!-- puntos finos -->
                        <div class="absolute inset-0 opacity-20"
                            style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,.35) 1px, transparent 0);
                                    background-size: 22px 22px;">
                        </div>

                        <div class="relative flex flex-col items-center text-center">
                            <div class="group relative">


                                <div
                                    class="absolute -inset-8 rounded-[2.75rem]
                    bg-white/10 blur-3xl
                    opacity-70 transition
                    group-hover:opacity-100">
                                </div>

                                <div
                                    class="relative rounded-[2.75rem]
                    bg-white/10
                    ring-1 ring-white/25
                    p-5
                    shadow-[0_25px_80px_-25px_rgba(255,255,255,.35)]">

                                    <img src="{{ $empresaActual?->logo_url }}" alt="{{ $empresaActual?->nombre }}"
                                        class="w-[16rem] xl:w-[20rem]
                       object-contain
                       rounded-2xl
                       
                       bg-white/90
                       p-6
                       shadow-xl
                       transition-transform duration-500
                       group-hover:scale-[1.03]"
                                        loading="lazy" />
                                </div>
                            </div>


                            <div class="mt-6 max-w-sm">
                                <p class="text-white/90 font-semibold tracking-tight">
                                    {{ $empresaActual?->nombre }}
                                </p>

                            </div>
                        </div>

                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-3 text-center text-xs text-slate-400 dark:text-slate-500">
                    © {{ date('Y') }} Tecnobyte360. Todos los derechos reservados.
                </div>

            </div>
        </div>
    </main>

    @livewireScripts
</body>

</html>
