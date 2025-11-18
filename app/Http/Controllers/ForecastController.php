<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class ForecastController extends Controller
{
    // Ubah jika Flask service berjalan di host/port lain
    protected $pyBase = 'http://127.0.0.1:5000';

    public function index()
    {
        return view('forecast.index');
    }
    // public function pred()
    // {
    //     return view('chart.forecast');
    // }

    // API proxy: daftar provinsi
    public function provinces()
    {
        $r = Http::get($this->pyBase . 'https://web-production-17bb.up.railway.app/api/provinces');
        if ($r->failed()) {
            return response()->json([], 500);
        }
        return $r->json();
    }

    // API proxy: forecast per provinsi
    public function forecast(Request $req)
    {
        $prov = $req->query('province');
        if (!$prov) return response()->json(['error' => 'province required'], 400);
        $r = Http::get($this->pyBase . 'https://web-production-17bb.up.railway.app/api/forecast', ['province' => $prov, 'periods' => 12]);
        if ($r->failed()) return response()->json($r->json(), $r->status());
        return $r->json();
    }

    // API proxy: choropleth (prediksi/actual summary)
    public function choropleth()
    {
        $r = Http::get($this->pyBase . 'https://web-production-17bb.up.railway.app/api/choropleth');
        if ($r->failed()) return response()->json([], 500);
        return $r->json();
    }

}
