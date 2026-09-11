<?php

namespace App\Models;

use App\Enums\SetorModelo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigModelo extends Model
{
    use HasFactory;

    protected $table = 'config_modelos';
    protected $primaryKey = 'cfg_mod_id';
    public $timestamps = false;

    protected $fillable = [
        'cfg_mod_nome',
        'cfg_mod_setor',
        'cfg_mod_ativo',
        'cfg_mod_usa_horarios',
        'cfg_mod_usa_clima',
        'cfg_mod_usa_servicos',
        'cfg_mod_usa_pecas',
        'cfg_mod_usa_ocorrencias',
        'cfg_mod_usa_observacoes',
    ];

    protected $casts = [
        'cfg_mod_setor' => SetorModelo::class,
        'cfg_mod_ativo' => 'boolean',
        'cfg_mod_usa_horarios' => 'boolean',
        'cfg_mod_usa_clima' => 'boolean',
        'cfg_mod_usa_servicos' => 'boolean',
        'cfg_mod_usa_pecas' => 'boolean',
        'cfg_mod_usa_ocorrencias' => 'boolean',
        'cfg_mod_usa_observacoes' => 'boolean',
    ];

    public function perguntas()
    {
        return $this->belongsToMany(
            ConfigPergunta::class,
            'config_modelos_perguntas',
            'cfg_mod_perg_modelo_id',
            'cfg_mod_perg_pergunta_id'
        )->withPivot(['cfg_mod_perg_id', 'cfg_mod_perg_ordem'])->orderBy('cfg_mod_perg_ordem');
    }

    public function naturezasAtendimentos()
    {
        return $this->hasMany(NaturezaAtendimento::class, 'nat_aten_config_modelo_id', 'cfg_mod_id');
    }

    /**
     * Secoes fixas do relatorio (Horarios/Clima/Servicos/Pecas/Ocorrencias/
     * Observacoes) que este modelo mantem visiveis - substitui as flags de
     * modelos_relatorios (ver migration 2026_09_11_090003 e
     * project_fae_bioenergia na memoria). Dados/Assinatura/Anexos nao tem
     * flag: sao sempre exibidos, independente do modelo.
     */
    public function secoesAtivas(): array
    {
        return [
            'horarios' => $this->cfg_mod_usa_horarios,
            'clima' => $this->cfg_mod_usa_clima,
            'servicos' => $this->cfg_mod_usa_servicos,
            'pecas' => $this->cfg_mod_usa_pecas,
            'ocorrencias' => $this->cfg_mod_usa_ocorrencias,
            'observacoes' => $this->cfg_mod_usa_observacoes,
        ];
    }
}
