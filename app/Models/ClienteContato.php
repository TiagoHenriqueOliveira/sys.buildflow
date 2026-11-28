<?php

namespace App\Models;

use App\Enums\NivelAcesso;
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
        'cli_cont_usuario_id',
    ];

    protected $casts = [
        'cli_cont_tipo' => TipoContatoCliente::class,
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cli_cont_cliente_id', 'cli_id');
    }

    /** Quem cadastrou o contato (nulo nos contatos anteriores a 28/11/2026). */
    public function autor()
    {
        return $this->belongsTo(Usuario::class, 'cli_cont_usuario_id', 'user_id');
    }

    /**
     * Auditoria 27/11/2026 (S-03, decisão D-01 do usuário): contato Técnico
     * é visível a todos que abrem o cliente; contato Comercial só ao autor,
     * ao vendedor responsável do cliente, ao perfil Comercial e a quem tem
     * acesso total. A Assistência, por exemplo, só vê os contatos Técnicos.
     */
    public function visivelPara(Usuario $usuario, Cliente $cliente): bool
    {
        if ($this->cli_cont_tipo !== TipoContatoCliente::Comercial) {
            return true;
        }

        if ($usuario->temAcessoTotal() || (int) $usuario->user_nivel_acesso === NivelAcesso::Comercial->value) {
            return true;
        }

        return ($this->cli_cont_usuario_id !== null && (int) $this->cli_cont_usuario_id === (int) $usuario->user_id)
            || ($cliente->cli_vendedor_id !== null && (int) $cliente->cli_vendedor_id === (int) $usuario->user_id);
    }
}
