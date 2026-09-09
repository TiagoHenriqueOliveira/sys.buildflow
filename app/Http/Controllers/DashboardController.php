<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    /**
     * Dashboard minimo/placeholder (ver resources/views/dashboard/index.blade.php).
     * Os endpoints de KPIs/gráficos que existiam aqui (herdados do MCL) foram
     * removidos junto com os cards/gráficos que os consumiam - cliente novo
     * (FAÉ Bioenergia) ainda sem dado real pra mostrar. Se/quando o dashboard
     * ganhar indicadores de verdade, reintroduzir os métodos de dados aqui.
     */
    public function index()
    {
        return view('dashboard.index');
    }
}
