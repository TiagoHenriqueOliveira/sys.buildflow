<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtendimentoAnexo extends Model
{
    protected $table = 'atendimentos_anexos';
    protected $primaryKey = 'aten_anexo_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_anexo_atendimento_id',
        'aten_anexo_path',
        'aten_anexo_nome_original',
    ];
}
