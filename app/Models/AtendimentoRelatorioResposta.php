<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtendimentoRelatorioResposta extends Model
{
    use HasFactory;

    protected $table = 'atendimentos_relatorios_respostas';
    protected $primaryKey = 'aten_rel_resp_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_rel_resp_relatorio_id',
        'aten_rel_resp_pergunta_id',
        'aten_rel_resp_valor',
    ];

    public function relatorio()
    {
        return $this->belongsTo(AtendimentoRelatorio::class, 'aten_rel_resp_relatorio_id', 'aten_rel_id');
    }

    public function pergunta()
    {
        return $this->belongsTo(ConfigPergunta::class, 'aten_rel_resp_pergunta_id', 'cfg_perg_id');
    }

    public function fotos()
    {
        return $this->hasMany(AtendimentoRelatorioRespostaFoto::class, 'aten_rel_resp_foto_resposta_id', 'aten_rel_resp_id');
    }
}
