@extends('layouts.app')

@section('content')
    <div class="bg-gray-50 text-gray-800">
        <section>
            <div class="max-w-7xl mx-auto" data-aos="fade-up">
                <div class="flex flex-col gap-6">
                    <!-- Sidebar -->
                    <div class="bg-white rounded shadow p-4 border-l-4 border-blue-500">
                        <label class="font-medium text-sm">Pilih Provinsi</label>
                        <select id="provinceSelect" class="w-full mt-2 border p-2 rounded"></select>
                    </div>

                    <!-- Kiri: konten grafik -->
                    <div class="grid grid-cols-2 gap-6">

                        <!-- CHART DATA ASLI -->
                        <div class="bg-white rounded shadow p-4">
                            <h3 class="text-lg font-semibold mb-2">Grafik Data BPS (Aktual)</h3>
                            <div class="h-[350px]">
                                <canvas id="actualChart"></canvas>
                            </div>
                        </div>

                        <!-- CHART PREDIKSI -->
                        <div class="bg-white rounded shadow p-4">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-lg font-semibold">Grafik Prediksi (Holt-Winters)</h3>
                                <div id="mapeDisplay" class="text-sm text-gray-600 hidden"></div>
                            </div>
                            <div class="h-[350px]">
                                <canvas id="forecastChart"></canvas>
                            </div>
                        </div>

                    </div>


                </div>
            </div>
        </section>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", async () => {
            const provinceSelect = document.getElementById("provinceSelect");
            const mapeDisplay = document.getElementById("mapeDisplay");

            const ctxActual = document.getElementById("actualChart").getContext("2d");
            const ctxForecast = document.getElementById("forecastChart").getContext("2d");

            let actualChart = new Chart(ctxActual, {
                type: "line",
                data: {
                    labels: [],
                    datasets: []
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            title: {
                                display: true,
                                text: "Jumlah Kunjungan"
                            }
                        }
                    }
                }
            });

            let forecastChart = new Chart(ctxForecast, {
                type: "line",
                data: {
                    labels: [],
                    datasets: []
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            title: {
                                display: true,
                                text: "Jumlah Kunjungan"
                            }
                        }
                    }
                }
            });

            function formatPeriod(date) {
                return new Date(date).toLocaleString("id-ID", {
                    month: "short",
                    year: "numeric"
                });
            }

            async function loadProvinces() {
                const res = await fetch("https://web-production-17bb.up.railway.app/api/provinces");
                const data = await res.json();
                provinceSelect.innerHTML = '<option value="">-- Pilih Provinsi --</option>';
                data.forEach(p => provinceSelect.innerHTML += `<option value="${p}">${p}</option>`);
            }

            async function updateCharts(prov) {
                if (!prov) return;

                const res = await fetch(`https://web-production-17bb.up.railway.app/api/forecast?province=${prov}`);
                const d = await res.json();

                // Chart Aktual
                actualChart.data.labels = d.test_index.map(formatPeriod);
                actualChart.data.datasets = [{
                    label: "Aktual",
                    data: d.test_values,
                    borderColor: "#1d4ed8",
                    tension: 0.3
                }];
                actualChart.update();

                // Chart Prediksi
                forecastChart.data.labels = d.pred_index.map(formatPeriod);
                forecastChart.data.datasets = [{
                    label: "Prediksi",
                    data: d.pred_values,
                    borderColor: "#ef4444",
                    tension: 0.3
                }];
                forecastChart.update();

                // MAPE
                mapeDisplay.textContent =
                    `MAPE: ${d.mape.toFixed(2)}% (Akurasi ${(100 - d.mape).toFixed(2)}%)`;
                mapeDisplay.classList.remove("hidden");
            }

            provinceSelect.addEventListener("change", () => updateCharts(provinceSelect.value));
            await loadProvinces();
        });
    </script>
@endsection
