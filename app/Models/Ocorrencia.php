<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ocorrencia extends Model
{
    use HasFactory;

    protected $table = 'ocorrencias';
    protected $primaryKey = 'ocor_id';
    public $timestamps = false;

    protected $fillable = [
        'ocor_descricao',
        'ocor_ativo',
    ];

    protected $casts = [
        'ocor_ativo' => 'boolean',
    ];
}
