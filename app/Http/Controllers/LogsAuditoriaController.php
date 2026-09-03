<?php

namespace App\Http\Controllers;

use App\Models\LogAuditoria;
use App\Models\LogErro;
use Illuminate\Http\Request;

class LogsAuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $modulos = LogAuditoria::distinct()->pluck('log_aud_modulo')->sort()->values();

        $query = LogAuditoria::query()->orderByDesc('log_aud_created_at');

        if ($request->filled('modulo')) {
            $query->where('log_aud_modulo', $request->modulo);
        }
        if ($request->filled('acao')) {
            $query->where('log_aud_acao', $request->acao);
        }
        if ($request->filled('data_de')) {
            $query->whereDate('log_aud_created_at', '>=', $request->data_de);
        }
        if ($request->filled('data_ate')) {
            $query->whereDate('log_aud_created_at', '<=', $request->data_ate);
        }

        $logs = $query->paginate(50)->withQueryString();

        $erros = LogErro::query()->orderByDesc('log_err_created_at')->limit(100)->get();

        return view('logs_auditoria.index', compact('logs', 'erros', 'modulos'));
    }
}
