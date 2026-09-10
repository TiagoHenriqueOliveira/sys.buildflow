<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtendimentoRelatorioRespostaFoto extends Model
{
    use HasFactory;

    protected $table = 'atendimentos_relatorios_respostas_fotos';
    protected $primaryKey = 'aten_rel_resp_foto_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_rel_resp_foto_resposta_id',
        'aten_rel_resp_foto_path',
        'aten_rel_resp_foto_comentario',
    ];

    public function resposta()
    {
        return $this->belongsTo(AtendimentoRelatorioResposta::class, 'aten_rel_resp_foto_resposta_id', 'aten_rel_resp_id');
    }
}
