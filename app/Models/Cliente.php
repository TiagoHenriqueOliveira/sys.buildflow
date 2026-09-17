<?php

namespace App\Models;

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
        'cli_contato_principal',
        'cli_vendedor_id',
        'cli_cnpj',
        'cli_inscricao_estadual',
        'cli_cidade',
        'cli_uf',
        'cli_segmento',
        'cli_equipamento_vendido',
        'cli_caso_sucesso',
        'cli_caso_sucesso_descricao',
        'cli_classificacao_id',
        'cli_dias_alerta_recontato',
        'cli_telefone',
        'cli_email',
        'cli_ativo',
        'cli_latitude',
        'cli_longitude',
        'cli_link_mapa',
    ];

    protected $casts = [
        'cli_ativo' => 'boolean',
        'cli_latitude' => 'float',
        'cli_longitude' => 'float',
        'cli_caso_sucesso' => 'boolean',
    ];

    public function vendedor()
    {
        return $this->belongsTo(Usuario::class, 'cli_vendedor_id', 'user_id');
    }

    public function classificacao()
    {
        return $this->belongsTo(ClassificacaoCliente::class, 'cli_classificacao_id', 'cla_cli_id');
    }

    public function contatos()
    {
        return $this->hasMany(ClienteContato::class, 'cli_cont_cliente_id', 'cli_id');
    }

    public function equipamentos()
    {
        return $this->hasMany(ClienteEquipamento::class, 'cli_equip_cliente_id', 'cli_id');
    }

    public function localizacoes()
    {
        return $this->hasMany(ClienteLocalizacao::class, 'cli_loc_cliente_id', 'cli_id');
    }

    public function temGeolocalizacao(): bool
    {
        return $this->cli_latitude !== null && $this->cli_longitude !== null;
    }

    public function atendimentos()
    {
        return $this->hasMany(Atendimento::class, 'aten_cliente_id', 'cli_id');
    }

    public function orcamentos()
    {
        return $this->hasMany(Orcamento::class, 'orc_cliente_id', 'cli_id');
    }

    /**
     * NC01 - historico consolidado (atendimentos + orcamentos), em ordem
     * cronologica decrescente. Pedido do cliente (2026-09-17): nao listar
     * relatorio aqui - a aba "Relatorios" do proprio cadastro de Atendimento
     * (ver AtendimentosController::getRelatorios()) ja cobre isso.
     */
    public function historico()
    {
        $itensAtendimento = $this->atendimentos->map(fn ($atendimento) => [
            'tipo' => 'Atendimento',
            'data' => $atendimento->aten_dt_inicio,
            'descricao' => trim('Atendimento aberto '.($atendimento->aten_responsavel ? '- '.$atendimento->aten_responsavel : '')),
            'link' => route('atendimentos.edit', $atendimento->aten_id),
        ]);

        $itensOrcamento = $this->orcamentos->map(fn ($orcamento) => [
            'tipo' => 'Orçamento',
            'data' => $orcamento->orc_criado_em,
            'descricao' => 'Orçamento'.(optional($orcamento->tipoOrcamento)->crm_tp_orc_nome ? ' - '.$orcamento->tipoOrcamento->crm_tp_orc_nome : ''),
            'link' => route('orcamentos.edit', $orcamento->orc_id),
        ]);

        return $itensAtendimento->concat($itensOrcamento)
            ->filter(fn ($item) => $item['data'] !== null)
            ->sortByDesc('data')
            ->values();
    }

    /**
     * Data do ultimo contato real com o cliente (atendimento OU orcamento) -
     * usado pelo comando de alerta de recontato. Nao monta o historico
     * inteiro (mais barato pra rodar em lote todo dia sobre todos os
     * clientes ativos).
     */
    public function dataUltimoContato(): ?\Illuminate\Support\Carbon
    {
        $ultimoAtendimento = $this->atendimentos()->max('aten_dt_inicio');
        $ultimoOrcamento = $this->orcamentos()->max('orc_criado_em');

        $datas = collect([$ultimoAtendimento, $ultimoOrcamento])
            ->filter()
            ->map(fn ($d) => \Illuminate\Support\Carbon::parse($d));

        return $datas->isEmpty() ? null : $datas->max();
    }
}
