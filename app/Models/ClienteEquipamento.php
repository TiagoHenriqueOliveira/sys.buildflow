<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteEquipamento extends Model
{
    use HasFactory;

    protected $table = 'clientes_equipamentos';
    protected $primaryKey = 'cli_equip_id';
    public $timestamps = false;

    protected $fillable = [
        'cli_equip_cliente_id',
        'cli_equip_descricao',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cli_equip_cliente_id', 'cli_id');
    }
}