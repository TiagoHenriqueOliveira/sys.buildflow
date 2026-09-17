<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Segmento extends Model
{
    use HasFactory;

    protected $table = 'segmentos';
    protected $primaryKey = 'seg_id';
    public $timestamps = false;

    protected $fillable = [
        'seg_descricao',
        'seg_ativo',
    ];

    protected $casts = [
        'seg_ativo' => 'boolean',
    ];
}