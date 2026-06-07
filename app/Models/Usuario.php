<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'user_id';
    public $timestamps = false;

    protected $fillable = [
        'user_nivel_acesso',
        'user_nome',
        'user_email',
        'user_senha',
        'user_ativo',
    ];

    protected $hidden = [
        'user_senha',
    ];

    protected $casts = [
        'user_ativo' => 'boolean',
        'user_nivel_acesso' => 'integer',
    ];

    public function getAuthPasswordName(): string
    {
        return 'user_senha';
    }
}
