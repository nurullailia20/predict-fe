@extends('layouts.app')

@section('content')
    <div class="bg-gray-50 text-gray-800">

        <!-- CONTENT SECTION (mirip contoh) -->
        <section class="w-full">
            <div class="max-w-7xl mx-auto" data-aos="fade-up">
                <!-- layout 2 kolom: kiri peta/grafik, kanan sidebar -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- Kiri: konten utama (peta + legenda + grafik) -->
                    <div class="lg:col-span-9 space-y-4">


                        <!-- MAIN MAP CARD -->
                        <div id="map-container" class="bg-white rounded-4 shadow-gray-800 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-lg font-semibold">Peta Sebaran Kunjungan Wisatawan Nusantara (2020 - 2022)
                                </h3>
                                <div class="text-sm text-gray-500">Sumber: Data internal</div>
                            </div>

                            <!-- ✅ WRAPPER UNTUK MAP -->
                            <div>
                                <div id="map" class="rounded-xl border-2 border-gray-300"></div>

                                <!-- Legend -->
                                <div class="mt-4 p-3 bg-white rounded border">
                                    <h4 class="font-semibold text-sm mb-2">Legenda Peta Choropleth</h4>
                                    <div id="legendContainer"
                                        class="grid grid-cols-2 lg:grid-cols-3 gap-6 text-sm text-gray-70"></div>
                                </div>
                            </div>
                        </div>

                        <!-- CHART CARD -->
                        <div id="chart-container" class="bg-white rounded-4 shadow-gray-800 p-4">

                            <div class="flex items-center justify-between mb-3 gap-3">
                                <div>
                                    <h3 class="text-lg font-semibold">Grafik Jumlah Kunjungan Wisatawan Nusantara (2020 -
                                        2022)</h3>
                                </div>
                            </div>

                            <!-- ✅ WRAPPER UNTUK CHART -->
                            <div class="h-[500px]">
                                <canvas id="kunjunganChart" style="width:100%; height:100%;"></canvas>
                            </div>
                        </div>

                    </div>

                    <!-- Kanan: Sidebar (filter) -->
                    <aside class="lg:col-span-3 border-l-4 border-solid border-blue-500">
                        <div class="bg-white rounded-4 shadow-gray-800  p-4">
                            <h4 class="font-semibold">Filter & Kontrol</h4>
                            <p class="text-sm text-gray-600 mb-3">Atur filter untuk memperbarui tampilan peta dan grafik.
                            </p>

                            <label class="text-sm font-medium">Pilih Provinsi</label>
                            <select id="provinceSelect" class="mt-2 block w-full border rounded p-2">
                                <option value="">-- Semua Provinsi --</option>
                            </select>

                            <div class="mt-3">
                                <label class="text-sm font-medium">Mode Tampilan</label>
                                <div class="mt-4 flex rounded">
                                    <button id="btn-map" class="flex-1 px-3 py-2 rounded-l-sm">Peta
                                    </button>
                                    <button id="btn-chart" class="flex-1 px-3 py-2 rounded-r-sm">Grafik
                                    </button>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="text-sm font-medium">Transparansi Choropleth</label>
                                <input id="opacityRange" type="range" min="0" max="100" value="80"
                                    class="w-full mt-2">
                            </div>

                            <div class="mt-4 text-sm text-gray-500">
                                <p><strong>Catatan:</strong> Model peramalan: <em>Holt-Winters</em> (seasonal p=12). Untuk
                                    hasil akurasi, sistem menampilkan nilai MAPE.</p>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </section>
    </div>


    <script>
        // Data GeoJSON dari Laravel (pastikan data di-escape dengan benar)
        // Jika Anda meneruskannya sebagai string JSON dari Controller:
        const geojsonData = {!! $geojson_data !!};
        let geojson;

        let mapInstance = null;
        let chartInstance = null;

        // Cek apakah ada pesan error dari Controller
        const errorMessage = "{{ $error_message }}";
        if (errorMessage && errorMessage !== 'null') {
            console.error("Kesalahan pemuatan data:", errorMessage);
            alert("Peta tidak dapat dimuat. Cek console untuk detail.");
        }

        // --- 1. Fungsi Persiapan Data untuk Chart ---
        function prepareChartData(geoJson) {
            const data = geoJson.features.map(f => ({
                provinsi: f.properties.PROVINSI,
                kunjungan: f.properties.TOTAL_KUNJUNGAN || 0
            }));

            // Urutkan data dari yang terbesar ke terkecil
            data.sort((a, b) => b.kunjungan - a.kunjungan);

            return {
                labels: data.map(d => d.provinsi),
                datasets: [{
                    label: 'Total Kunjungan (2020-2022)',
                    data: data.map(d => d.kunjungan),
                    backgroundColor: 'rgba(59, 130, 246, 0.7)', // Warna biru Tailwind
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }]
            };
        }

        // --- 2. Fungsi Inisialisasi Grafik ---
        function initializeChart() {
            if (chartInstance) return; // Jangan inisialisasi ulang jika sudah ada

            const ctx = document.getElementById('kunjunganChart').getContext('2d');
            const chartData = prepareChartData(geojsonData);

            chartInstance = new Chart(ctx, {
                type: 'bar',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y', // Membuat Bar Horizontal
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Total Kunjungan'
                            },
                            ticks: {
                                callback: function(value, index, values) {
                                    // Format angka ke format ribuan
                                    return value.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.x !== null) {
                                        label += context.parsed.x.toLocaleString('id-ID');
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }

        // --- 3. Fungsi Kontrol Tampilan (Toggle) ---

        const mapContainer = document.getElementById('map-container');
        const chartContainer = document.getElementById('chart-container');
        const btnMap = document.getElementById('btn-map');
        const btnChart = document.getElementById('btn-chart');

        function toggleView(view) {
            if (view === 'map') {
                mapContainer.style.display = 'block';
                chartContainer.style.display = 'none';
                btnMap.classList.add("bg-blue-600", "text-white");
                btnMap.classList.remove(
                    "border",
                    "border-blue-600",
                    "text-blue-600"
                );
                btnChart.classList.remove("bg-blue-600", "text-white");
                btnChart.classList.add(
                    "border",
                    "border-blue-600",
                    "text-blue-600"
                );
                // Jika beralih kembali ke peta, pastikan peta di-invalidate agar tampil sempurna
                if (mapInstance) {
                    mapInstance.invalidateSize();
                }
            } else { // view === 'chart'
                mapContainer.style.display = 'none';
                chartContainer.style.display = 'block';
                btnChart.classList.add("bg-blue-600", "text-white");
                btnChart.classList.remove(
                    "border",
                    "border-blue-600",
                    "text-blue-600"
                );
                btnMap.classList.remove("bg-blue-600", "text-white");
                btnMap.classList.add("border", "border-blue-600", "text-blue-600");
                // Inisialisasi grafik saat pertama kali dibuka
                initializeChart();
            }
        }


        // Listener untuk Tombol
        btnMap.addEventListener('click', () => toggleView('map'));
        btnChart.addEventListener('click', () => toggleView('chart'));

        // --- 4. Inisialisasi Peta Leaflet ---

        // --- FUNGSI CHOROPLETH ---

        // Rentang (thresholds) untuk pewarnaan
        function getColor(d) {
            return d > 30000000 ? '#4a1486' : // Ungu Tua
                d > 10000000 ? '#884ea0' : // Ungu Sedang
                d > 5000000 ? '#b266b7' : // Ungu Muda
                d > 1000000 ? '#cc88cc' : // Merah Jambu Gelap
                d > 500000 ? '#e6b3e6' : // Merah Jambu Sedang
                '#f3e0f3'; // Warna Paling Muda (untuk nilai kecil/0)
        }

        // Fungsi untuk menentukan style berdasarkan fitur (opsional: penyesuaian warna)
        function style(feature) {
            const totalKunjungan = feature.properties.TOTAL_KUNJUNGAN || 0;
            return {
                fillColor: getColor(totalKunjungan), // Dapatkan warna berdasarkan data
                weight: 1,
                opacity: 1,
                color: 'white',
                dashArray: '3',
                fillOpacity: 0.85
            };
        }

        function renderLegend() {
            const legendContainer = document.getElementById('legendContainer');
            legendContainer.innerHTML = ""; // Kosongkan dulu

            const grades = [0, 500001, 1000001, 5000001, 10000001, 30000001];

            for (let i = 0; i < grades.length; i++) {
                const from = grades[i];
                const to = grades[i + 1];

                const color = getColor(from + 1);

                const item = document.createElement("div");
                item.className = "flex items-center space-x-2";

                item.innerHTML = `
            <span class="inline-block w-5 h-5 rounded-sm" style="background:${color}"></span>
            <span>${from.toLocaleString('id-ID')}${to ? ' - ' + to.toLocaleString('id-ID') : ' +'}</span>
        `;

                legendContainer.appendChild(item);
            }
        }


        function highlightFeature(e) {
            const layer = e.target;
            const props = layer.feature.properties;

            // Redupkan semua layer lain kecuali yg di-hover
            geojson.eachLayer(function(l) {
                if (l !== layer) {
                    l.setStyle({
                        fillOpacity: 0.2,
                        color: '#ccc'
                    });
                }
            });

            // Highlight layer yang di-hover
            layer.setStyle({
                weight: 4,
                color: '#666',
                dashArray: '',
                fillOpacity: 1
            });

            if (!L.Browser.ie && !L.Browser.opera && !L.Browser.edge) {
                layer.bringToFront();
            }

            const provinceName = layer.feature.properties.PROVINSI || 'N/A';
            const totalKunjungan = layer.feature.properties.TOTAL_KUNJUNGAN || 0;

            layer.bindPopup(
                `<b>${provinceName}</b><br/>` +
                `Total Kunjungan: ${totalKunjungan.toLocaleString('id-ID')}`, {
                    closeButton: false
                }
            ).openPopup();

            info.update(props);

        }

        function resetHighlight(e) {
            // Mengatur ulang style ke default
            geojson.resetStyle(e.target);
            // map.closePopup(); // Tutup popup saat reset
            info.update();
        }

        function onEachFeature(feature, layer) {
            // Daftarkan event listener
            layer.on({
                mouseover: highlightFeature,
                mouseout: resetHighlight,
                click: zoomSmooth // Uncomment jika ingin ada fungsi zoom saat klik
            });
        }
        console.log(geojsonData.features[0].properties);

        // === 5. Isi Dropdown Provinsi dari GeoJson ===
        function populateProvinceSelect() {
            const select = document.getElementById('provinceSelect');

            // Ambil nama provinsi dan buang duplikat
            const provinces = [...new Set(geojsonData.features.map(f => f.properties.PROVINSI))];

            provinces.sort(); // Sort alfabetis

            provinces.forEach(prov => {
                const option = document.createElement("option");
                option.value = prov;
                option.textContent = prov;
                select.appendChild(option);
            });
        }

        // === EventListener higlight provinsi saat select ===
        document.getElementById('provinceSelect').addEventListener('change', function() {
            const selected = this.value;
            const opacityValue = opacitySlider.value / 100;

            if (!selected) {
                // reset tampilan seperti semula
                geojson.resetStyle();
                mapInstance.fitBounds(geojson.getBounds());
                return;
            }

            geojson.eachLayer(function(layer) {
                const props = layer.feature.properties;
                if (props.PROVINSI === selected) {
                    layer.setStyle({
                        weight: 4,
                        color: '#000',
                        fillOpacity: 1
                    });
                    mapInstance.fitBounds(layer.getBounds());
                } else {
                    layer.setStyle({
                        fillOpacity: 0.2,
                        color: '#ccc'
                    });
                }
            });
        });

        function zoomSmooth(e) {
            const layer = e.target;

            // Zoom pelan ke provinsi yang diklik (durasi ~1 detik)
            mapInstance.flyToBounds(layer.getBounds(), {
                padding: [40, 40], // agar tidak mepet frame
                duration: 1.3 // semakin besar -> semakin pelan
            });
            // Sementara matikan efek hover saat zoom
            layer.off('mouseover', highlightFeature);
            layer.off('mouseout', resetHighlight);

            // Aktifkan lagi setelah zoom selesai
            setTimeout(() => {
                layer.on('mouseover', highlightFeature);
                layer.on('mouseout', resetHighlight);
            }, 1400);
        }

        // === FIX ERROR: "info is not defined" === //
        const info = L.control();

        info.onAdd = function(map) {
            this._div = L.DomUtil.create('div', 'info');
            this.update();
            return this._div;
        };

        // Update isi panel info
        info.update = function(props) {
            this._div.innerHTML = '<h4>Kunjungan Wisatawan</h4>' +
                (props ?
                    '<b>' + props.PROVINSI + '</b><br />' +
                    'Total: ' + (props.TOTAL_KUNJUNGAN || 0).toLocaleString('id-ID') :
                    'Arahkan kursor pada provinsi');
        };

        // Tambahkan ke peta setelah peta dibuat



        // Inisialisasi Peta
        function initializeMap() {
            if (mapInstance) return mapInstance;

            // Atur koordinat awal agar peta fokus ke Indonesia
            const map = L.map('map').setView([-2.5, 118.0], 5);
            mapInstance = map;

            // Tambahkan Tile Layer (Peta Dasar)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            // Tambahkan GeoJSON ke Peta
            geojson = L.geoJson(geojsonData, {
                style: style,
                onEachFeature: onEachFeature
            }).addTo(map);

            info.addTo(map);   // ✅ FIX PENTING, tanpa ini error

            // Sesuaikan tampilan peta agar mencakup semua data GeoJSON
            map.fitBounds(geojson.getBounds());

            // legend.addTo(map);
            renderLegend();

            return map;
        }

        // === 6. Kontrol Transparansi Choropleth ===
        const opacitySlider = document.getElementById('opacityRange');

        opacitySlider.addEventListener('input', function() {
            const opacityValue = this.value / 100; // convert 0–100 menjadi 0.0–1.0

            geojson.setStyle(function(feature) {
                const totalKunjungan = feature.properties.TOTAL_KUNJUNGAN || 0;
                return {
                    fillColor: getColor(totalKunjungan),
                    weight: 1,
                    opacity: 1,
                    color: 'white',
                    dashArray: '3',
                    fillOpacity: opacityValue // penting!
                };
            });
        });


        // Panggil inisialisasi peta saat dokumen dimuat
        initializeMap();

        // Pastikan tampilan awal adalah peta
        toggleView('map');

        populateProvinceSelect();
    </script>
@endsection
