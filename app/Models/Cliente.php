<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';
    protected $primaryKey = 'cli_id';
    public $timestamps = false;

    protected $fillable = [
        'cli_nome',
        'cli_unidade',
        'cli_cnpj',
        'cli_cidade',
        'cli_uf',
        'cli_telefone',
        'cli_email',
        'cli_ativo',
    ];

    protected $casts = [
        'cli_ativo' => 'boolean',
    ];

    /**
     * Nome para exibição, com a unidade ao lado e sempre com o rótulo:
     * "AMBIENTAL MS PANTANAL SPE S.A. - Unidade: Itaquiraí". Usado nas
     * telas, no PDF e na API do app — o app mostra o texto pronto que vem
     * daqui, então a unidade aparece lá sem precisar de APK novo.
     * Estático para servir também a consultas com join (sem o model).
     */
    public static function formatarNome(?string $nome, ?string $unidade): string
    {
        $nome    = (string) $nome;
        $unidade = trim((string) $unidade);

        return $unidade === '' ? $nome : "{$nome} - Unidade: {$unidade}";
    }

    protected function nomeExibicao(): Attribute
    {
        return Attribute::get(fn () => self::formatarNome($this->cli_nome, $this->cli_unidade));
    }
}
