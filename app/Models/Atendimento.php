<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Atendimento extends Model
{
    use HasFactory;

    protected $table = 'atendimentos';
    protected $primaryKey = 'aten_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_natureza_id',
        'aten_cliente_id',
        'aten_usuario_id',
        'aten_status',
        'aten_nr_proposta',
        'aten_descricao',
        'aten_responsavel',
        'aten_endereco',
        'aten_dt_inicio',
        'aten_dt_fim',
    ];

    protected $casts = [
        'aten_status' => 'integer',
        'aten_dt_inicio' => 'date',
        'aten_dt_fim' => 'date',
    ];

    public function natureza()
    {
        return $this->belongsTo(NaturezaAtendimento::class, 'aten_natureza_id', 'nat_aten_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'aten_cliente_id', 'cli_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'aten_usuario_id', 'user_id');
    }
}
