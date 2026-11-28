<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /** Contatos que o usuário pode ver (S-03 / D-01 — ver ClienteContato::visivelPara). */
    public function contatosVisiveisPara(?Usuario $usuario): \Illuminate\Support\Collection
    {
        return $usuario
            ? $this->contatos->filter(fn (ClienteContato $c) => $c->visivelPara($usuario, $this))->values()
            : $this->contatos;
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

    /**
     * Perfil Vendedor (2026-11-27): só os clientes em que ele é o vendedor
     * responsável e os que ainda não têm vendedor definido (decisão do
     * cliente: um prospect sem vendedor continua visível a todos).
     */
    public function scopeVisivelPara(Builder $query, Usuario $usuario): Builder
    {
        if (! $usuario->veSoOsProprios()) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->whereNull('cli_vendedor_id')
            ->orWhere('cli_vendedor_id', $usuario->user_id));
    }

    public function visivelPara(Usuario $usuario): bool
    {
        return ! $usuario->veSoOsProprios()
            || $this->cli_vendedor_id === null
            || (int) $this->cli_vendedor_id === (int) $usuario->user_id;
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
    /** Visitas deste cliente em roteiros de viagem (CRM05/06). */
    public function visitasRoteiro()
    {
        return $this->hasMany(RoteiroViagemCliente::class, 'crm_rot_cli_cliente_id', 'cli_id');
    }

    /**
     * Histórico consolidado do cliente (aba Histórico). Desde 2026-11-27
     * inclui os roteiros de viagem: a data mostra o período do roteiro
     * (data_exibicao) e a descrição, a observação da visita a este cliente.
     * Com $usuario, os roteiros seguem a visibilidade dele (perfil Vendedor
     * só vê os próprios).
     */
    public function historico(?Usuario $usuario = null)
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

        $itensRoteiro = $this->visitasRoteiro()
            ->with('roteiro')
            ->whereHas('roteiro', fn ($q) => $usuario ? $q->visivelPara($usuario) : $q)
            ->get()
            ->map(fn ($visita) => [
                'tipo' => 'Roteiro de viagem',
                'data' => $visita->roteiro->crm_rot_periodo_inicio,
                'data_exibicao' => $visita->roteiro->crm_rot_periodo_inicio->format('d/m/Y')
                    . ' a ' . $visita->roteiro->crm_rot_periodo_fim->format('d/m/Y'),
                'descricao' => filled($visita->crm_rot_cli_observacao) ? $visita->crm_rot_cli_observacao : 'Sem observação',
                'link' => route('roteiros-viagem.edit', $visita->crm_rot_cli_roteiro_id),
            ]);

        return $itensAtendimento->concat($itensOrcamento)->concat($itensRoteiro)
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
