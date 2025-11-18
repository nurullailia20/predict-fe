<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Ambil data GeoJSON
        $geojsonPath = public_path('data/38 Provinsi Indonesia - Provinsi.json');
        if (!File::exists($geojsonPath)) {
            // Handle error jika file GeoJSON tidak ditemukan
            return view('dashboard.index', ['geojson_data' => '{"type": "FeatureCollection", "features": []}', 'province_data' => [], 'error_message' => 'File GeoJSON tidak ditemukan di :' . $geojsonPath]);
        }
        $geojson_content = File::get($geojsonPath);

        // 2. Ambil dan Proses Data Kunjungan CSV
        $csvPath = public_path('data/data kunjungan 2020-2022.csv');
        $provinceData = $this->processProvinceData($csvPath);

        // 3. Gabungkan Data Kunjungan ke GeoJSON
        // Konversi GeoJSON string ke array PHP
        $geojsonArray = json_decode($geojson_content, true);

        // Loop dan tambahkan data kunjungan ke properti setiap fitur GeoJSON
        foreach ($geojsonArray['features'] as &$feature) {
            $provinceName = $feature['properties']['PROVINSI'];

            // Lakukan normalisasi nama (misal: hilangkan "KEP.", "DKI ", "DI ", "NUSA TENGGARA ")
            $normalizedName = $this->normalizeProvinceName($provinceName);

            $feature['properties']['TOTAL_KUNJUNGAN'] = $provinceData[$normalizedName]['total'] ?? 0;
        }

        // Konversi kembali ke string JSON untuk Blade
        $final_geojson_content = json_encode($geojsonArray);

        return view('dashboard.index', [
            'geojson_data' => $final_geojson_content,
            'error_message' => null
        ]);
    }

    /**
     * Memproses CSV untuk mendapatkan total kunjungan per provinsi.
     * @param string $filePath
     * @return array
     */
    private function processProvinceData(string $filePath): array
    {
        if (!File::exists($filePath)) {
            return [];
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $result = [];

        // Header: Provinsi;Tahun;Bulan;Kunjungan
        $header = str_getcsv(array_shift($lines), ';');

        foreach ($lines as $line) {
            $data = str_getcsv($line, ';');
            if (count($data) < 4) continue;

            $provinsi = $data[0];
            $kunjungan = (int) $data[3];

            // Normalisasi nama provinsi dari CSV (sesuaikan dengan GeoJSON jika perlu)
            $normalizedName = $this->normalizeProvinceName($provinsi);

            $result[$normalizedName]['total'] = ($result[$normalizedName]['total'] ?? 0) + $kunjungan;
        }

        return $result;
    }

    /**
     * Fungsi pembantu untuk normalisasi nama provinsi agar cocok antara GeoJSON dan CSV.
     * (Misalnya, GeoJSON menggunakan "DKI JAKARTA" dan CSV menggunakan "DKI JAKARTA")
     * Anda mungkin perlu menyesuaikan ini berdasarkan data Anda.
     */
    private function normalizeProvinceName(string $name): string
    {
        $name = trim(strtoupper($name));
        // --- 1. Pemetaan Nama Khusus ---
        // Buat daftar pemetaan (CSV Name => GeoJSON Standard Name)
        $mapping = [
            'DI YOGYAKARTA' => 'DAERAH ISTIMEWA YOGYAKARTA',
            'KEP. BANGKA BELITUNG' => 'KEPULAUAN BANGKA BELITUNG',
            'KEP. RIAU' => 'KEPULAUAN RIAU',
            // Tambahkan pemetaan lain jika ada ketidaksesuaian:
        ];

        if (isset($mapping[$name])) {
            return $mapping[$name];
        }

        // --- 2. Normalisasi Umum (Hilangkan singkatan dan spasi ekstra) ---
        // Jika tidak ada di pemetaan khusus, lakukan pembersihan umum
        $name = preg_replace('/\s+/', ' ', $name); // Ganti spasi ganda dengan spasi tunggal

        return $name;
    }
}
