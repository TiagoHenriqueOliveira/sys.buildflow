<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteLocalizacao extends Model
{
    use HasFactory;

    protected $table = 'clientes_localizacoes';
    protected $primaryKey = 'cli_loc_id';
    public $timestamps = false;

    protected $fillable = [
        'cli_loc_cliente_id',
        'cli_loc_descricao',
        'cli_loc_latitude',
        'cli_loc_longitude',
    ];

    protected $casts = [
        'cli_loc_latitude' => 'float',
        'cli_loc_longitude' => 'float',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cli_loc_cliente_id', 'cli_id');
    }
}