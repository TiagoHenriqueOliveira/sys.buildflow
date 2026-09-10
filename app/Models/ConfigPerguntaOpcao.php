<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigPerguntaOpcao extends Model
{
    use HasFactory;

    protected $table = 'config_perguntas_opcoes';
    protected $primaryKey = 'cfg_perg_op_id';
    public $timestamps = false;

    protected $fillable = [
        'cfg_perg_op_pergunta_id',
        'cfg_perg_op_texto',
    ];

    public function pergunta()
    {
        return $this->belongsTo(ConfigPergunta::class, 'cfg_perg_op_pergunta_id', 'cfg_perg_id');
    }
}
