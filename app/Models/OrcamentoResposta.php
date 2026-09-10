<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrcamentoResposta extends Model
{
    use HasFactory;

    protected $table = 'orcamentos_respostas';
    protected $primaryKey = 'orc_resp_id';
    public $timestamps = false;

    protected $fillable = [
        'orc_resp_orcamento_id',
        'orc_resp_pergunta_id',
        'orc_resp_valor',
    ];

    public function orcamento()
    {
        return $this->belongsTo(Orcamento::class, 'orc_resp_orcamento_id', 'orc_id');
    }

    public function pergunta()
    {
        return $this->belongsTo(ConfigPergunta::class, 'orc_resp_pergunta_id', 'cfg_perg_id');
    }
}