<?php

namespace App\Http\Requests\Mcl;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssinaturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // 'nullable' é necessário além de 'required_with': sem ele, quando
        // 'cliente' não vem preenchido, cliente_nome/cliente_cpf chegam como
        // null e 'string'/'max' rejeitam esse null mesmo não sendo mais
        // obrigatórios (mesmo bug corrigido em AtendimentoRelatorioAssinaturasRequest,
        // a versão web deste mesmo endpoint).
        return [
            'tecnico'      => ['nullable', 'string'],
            'cliente'      => ['nullable', 'string'],
            'cliente_nome' => ['nullable', 'required_with:cliente', 'string', 'max:100'],
            'cliente_cpf'  => ['nullable', 'required_with:cliente', 'string', 'max:14'],
        ];
    }
}
