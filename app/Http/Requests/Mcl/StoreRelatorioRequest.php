<?php

namespace App\Http\Requests\Mcl;

use Illuminate\Foundation\Http\FormRequest;

class StoreRelatorioRequest extends FormRequest
{
    public function authorize(): bool
    {
        // A checagem de posse do atendimento continua no controller
        // (Api\Mcl\RelatoriosController::store), que já responde com o
        // formato JSON de erro padrão do resto da API Mcl em vez do formato
        // genérico que uma falha aqui produziria.
        return true;
    }

    public function rules(): array
    {
        return [
            'aten_rel_data' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }
}
