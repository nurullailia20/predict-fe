<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Prediksi Kunjungan Wisatawan Nusantara') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .leaflet-interactive {
            transition: fill-opacity 0.35s ease, opacity 0.35s ease, stroke-width 0.35s ease;
        }

        #map {
            height: 500px;
        }

        #chart-container {
            height: 500px;
            display: none;
        }
    </style>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
</head>

<body class="bg-gray-100 text-gray-800 font-sans antialiased">
    <!-- HEADER -->
    <header class="bg-blue-600 text-white">
        <div class="max-w-7xl mx-auto p-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="bg-white rounded p-1">
                    <img src="" alt="logo" style="height:36px" onerror="this.style.display='none'">
                </div>
                <div>
                    <div class="text-sm opacity-80">Sistem Informasi Prediksi</div>
                    <div class="text-lg font-semibold">Prediksi Kunjungan Wisatawan Nusantara</div>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <nav class="hidden md:flex gap-6">
                    <a href="/" class="text-white text-sm hover:underline">Dashboard</a>
                    <a href="{{ route('perbandingan.prediksi') }}" class="text-white text-sm hover:underline">Prediksi</a>
                    <a href="#" class="text-white text-sm hover:underline">Statistik</a>
                </nav>
            </div>
        </div>
    </header>

    {{-- Content --}}
    <main class="mx-auto py-6 px-4">
        @yield('content')
    </main>
</body>

</html>
