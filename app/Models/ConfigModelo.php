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
    ];

    protected $casts = [
        'cfg_mod_setor' => SetorModelo::class,
        'cfg_mod_ativo' => 'boolean',
    ];

    public function perguntas()
    {
        return $this->belongsToMany(
            ConfigPergunta::class,
            'config_modelos_perguntas',
            'cfg_mod_perg_modelo_id',
            'cfg_mod_perg_pergunta_id'
        )->withPivot(['cfg_mod_perg_id', 'cfg_mod_perg_ordem', 'cfg_mod_perg_sessao_id'])->orderBy('cfg_mod_perg_ordem');
    }

    public function naturezasAtendimentos()
    {
        return $this->hasMany(NaturezaAtendimento::class, 'nat_aten_config_modelo_id', 'cfg_mod_id');
    }

    /**
     * Pedido do cliente (2026-09-14) — perguntas marcadas como "Sessão"
     * (cfg_perg_e_sessao), em ordem (mesma ordem de `perguntas()`).
     */
    public function sessoes()
    {
        return $this->perguntas->where('cfg_perg_e_sessao', true)->values();
    }

    /**
     * Agrupa as perguntas de verdade (não-sessão) em "genéricas" (sem
     * sessão vinculada) e "por sessão" (id da pergunta-sessão => perguntas
     * vinculadas a ela via cfg_mod_perg_sessao_id — vínculo explícito,
     * escolhido em Configurador > Modelos, não mais por ordem/adjacência
     * na lista - pedido do cliente em 2026-09-14, ver migration
     * add_sessao_id_to_config_modelos_perguntas_table). Usado tanto para
     * montar as abas do relatório quanto para agrupar as respostas
     * retornadas ao preencher.
     */
    public function perguntasAgrupadasPorSessao(): array
    {
        $genericas = collect();
        $porSessao = collect();

        foreach ($this->perguntas as $pergunta) {
            if ($pergunta->cfg_perg_e_sessao) {
                $porSessao[$pergunta->cfg_perg_id] = collect();
            }
        }

        foreach ($this->perguntas as $pergunta) {
            if ($pergunta->cfg_perg_e_sessao) {
                continue;
            }

            $sessaoId = $pergunta->pivot->cfg_mod_perg_sessao_id;
            if ($sessaoId && $porSessao->has($sessaoId)) {
                $porSessao[$sessaoId]->push($pergunta);
            } else {
                $genericas->push($pergunta);
            }
        }

        return ['genericas' => $genericas, 'por_sessao' => $porSessao];
    }
}