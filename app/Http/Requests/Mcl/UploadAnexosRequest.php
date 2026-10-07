<?php

namespace App\Http\Requests\Mcl;

use Illuminate\Foundation\Http\FormRequest;

class UploadAnexosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fotos'      => ['nullable', 'array'],
            'fotos.*'    => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp'],
            'legendas'   => ['nullable', 'array'],
            'legendas.*' => ['nullable', 'string', 'max:255'],
            'videos'     => ['nullable', 'array'],
            'videos.*'   => ['file', 'max:524288', 'mimes:mp4,mov,avi,mkv,webm'],
            'arquivos'   => ['nullable', 'array'],
            'arquivos.*' => ['file', 'max:524288', 'mimes:pdf,doc,docx,xls,xlsx,txt,csv'],
        ];
    }
}
