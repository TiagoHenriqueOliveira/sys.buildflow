<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'user_id';
    public $timestamps = false;

    protected $fillable = [
        'user_nivel_acesso',
        'user_nome',
        'user_email',
        'user_senha',
        'user_ativo',
        'user_protegido',
    ];

    protected $hidden = [
        'user_senha',
    ];

    protected $casts = [
        'user_ativo'       => 'boolean',
        'user_protegido'   => 'boolean',
        'user_nivel_acesso' => 'integer',
    ];

    public function isProtegido(): bool
    {
        return (bool) $this->user_protegido;
    }

    public function getAuthPasswordName(): string
    {
        return 'user_senha';
    }
}
