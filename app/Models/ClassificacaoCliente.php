<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassificacaoCliente extends Model
{
    use HasFactory;

    protected $table = 'classificacoes_cliente';
    protected $primaryKey = 'cla_cli_id';
    public $timestamps = false;

    protected $fillable = [
        'cla_cli_nome',
        'cla_cli_ativo',
    ];

    protected $casts = [
        'cla_cli_ativo' => 'boolean',
    ];
}
