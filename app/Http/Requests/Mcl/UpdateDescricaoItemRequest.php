<?php

namespace App\Http\Requests\Mcl;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * RF013 — POST /relatorios/{id}/descricao-itens/{item_id} (contrato 3.4),
 * multipart. Também usado pela rota web equivalente.
 */
class UpdateDescricaoItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'texto' => ['required', 'string'],
            'foto' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp'],
            // Multipart só manda texto: aceita "1"/"0" e também "true"/"false".
            'remover_foto' => ['nullable', 'in:0,1,true,false'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->hasFile('foto') && $this->removerFoto()) {
                $validator->errors()->add('remover_foto', 'Envie a foto nova ou peça para remover a foto, não os dois.');
            }
        });
    }

    public function removerFoto(): bool
    {
        return $this->boolean('remover_foto');
    }

    public function messages(): array
    {
        return [
            'texto.required' => 'Descreva o item.',
            'foto.file' => 'A foto enviada é inválida.',
            'foto.max' => 'A foto não pode ultrapassar 10 MB.',
            'foto.mimes' => 'Tipo de imagem não permitido. Formatos aceitos: JPG, JPEG, PNG, WEBP.',
            'remover_foto.in' => 'Valor inválido para remover_foto (use 1 ou 0).',
        ];
    }
}
