<?php

namespace App\Models;

use App\Enums\TipoContatoCliente;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteContato extends Model
{
    use HasFactory;

    protected $table = 'clientes_contatos';
    protected $primaryKey = 'cli_cont_id';
    public $timestamps = false;

    protected $fillable = [
        'cli_cont_cliente_id',
        'cli_cont_nome',
        'cli_cont_cargo',
        'cli_cont_telefone',
        'cli_cont_email',
        'cli_cont_tipo',
    ];

    protected $casts = [
        'cli_cont_tipo' => TipoContatoCliente::class,
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cli_cont_cliente_id', 'cli_id');
    }
}
