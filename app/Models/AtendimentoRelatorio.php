<?php

namespace App\Models;

use App\Enums\AssinaturaTipo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Atendimento;
use App\Models\AtendimentoRelatorioAssinatura;
use App\Models\AtendimentoRelatorioServico;
use App\Models\AtendimentoRelatorioPeca;
use App\Models\ModeloRelatorio;

class AtendimentoRelatorio extends Model
{
    use HasFactory;

    protected $table = 'atendimentos_relatorios';
    protected $primaryKey = 'aten_rel_id';
    public $timestamps = false;

    protected $fillable = [
        'aten_rel_atendimento_id',
        'aten_rel_modelo_relatorio_id',
        'aten_rel_config_modelo_id',
        'aten_rel_data',
        'aten_rel_status',
        'aten_rel_descricao',
        'aten_rel_informacoes_adicionais',
        'aten_rel_observacao_interna',
        'aten_rel_dt_fim',
        'aten_rel_aprovado_por',
        'aten_rel_aprovado_em',
        'aten_rel_observacao_supervisor',
    ];

    protected $casts = [
        'aten_rel_data'   => 'date:Y-m-d',
        'aten_rel_status' => 'integer',
        'aten_rel_aprovado_em' => 'datetime',
    ];

    public function atendimento()
    {
        return $this->belongsTo(
            Atendimento::class,
            'aten_rel_atendimento_id',
            'aten_id'
        );
    }

    public function modeloRelatorio()
    {
        return $this->belongsTo(
            ModeloRelatorio::class,
            'aten_rel_modelo_relatorio_id',
            'mod_rel_id'
        );
    }

    /**
     * Sessao 08 - Configurador substitui modelos_relatorios. Todo relatorio
     * NOVO recebe este vinculo (copiado de natureza.configModelo na
     * criacao, ver AtendimentosRelatoriosController::store()); relatorios
     * antigos, migrados via backfill (2026_09_11_090007), tambem passam a
     * ter um config_modelo equivalente - so um relatorio orfao (natureza
     * sem nenhum modelo legado configurado) ficaria sem nenhum dos dois.
     */
    public function configModelo()
    {
        return $this->belongsTo(
            ConfigModelo::class,
            'aten_rel_config_modelo_id',
            'cfg_mod_id'
        );
    }

    public function aprovadoPor()
    {
        return $this->belongsTo(
            Usuario::class,
            'aten_rel_aprovado_por',
            'user_id'
        );
    }

    public function compartilhamentos()
    {
        return $this->hasMany(
            AtendimentoRelatorioCompartilhamento::class,
            'aten_rel_comp_relatorio_id',
            'aten_rel_id'
        )->orderByDesc('aten_rel_comp_criado_em');
    }

    public function horarios()
    {
        return $this->hasOne(
            AtendimentoRelatorioHorario::class,
            'aten_rel_hora_relatorio_id',
            'aten_rel_id'
        );
    }

    public function climas()
    {
        return $this->hasMany(
            AtendimentoRelatorioCondicaoClimatica::class,
            'aten_rel_clima_relatorio_id',
            'aten_rel_id'
        );
    }

    public function fotos()
    {
        return $this->hasMany(
            AtendimentoRelatorioFoto::class,
            'aten_rel_foto_relatorio_id',
            'aten_rel_id'
        );
    }

    public function videos()
    {
        return $this->hasMany(
            AtendimentoRelatorioVideo::class,
            'aten_rel_vid_relatorio_id',
            'aten_rel_id'
        );
    }

    public function anexos()
    {
        return $this->hasMany(
            AtendimentoRelatorioAnexo::class,
            'aten_rel_anexo_relatorio_id',
            'aten_rel_id'
        );
    }

    public function assinaturas()
    {
        return $this->hasMany(
            AtendimentoRelatorioAssinatura::class,
            'aten_rel_ass_relatorio_id',
            'aten_rel_id'
        );
    }

    public function assinaturaResponsavel(): ?AtendimentoRelatorioAssinatura
    {
        return $this->assinaturas()->where('aten_rel_ass_tipo', AssinaturaTipo::Responsavel)->first();
    }

    public function assinaturaCliente(): ?AtendimentoRelatorioAssinatura
    {
        return $this->assinaturas()->where('aten_rel_ass_tipo', AssinaturaTipo::Cliente)->first();
    }

    /**
     * Retorna os dados de prazo calculados a partir das datas do atendimento.
     * Centraliza a lógica usada em show(), getData() e pdf().
     */
    public function calcularPrazo(): array
    {
        $inicio = Carbon::parse($this->atendimento->aten_dt_inicio);
        $fim    = Carbon::parse($this->atendimento->aten_dt_fim);
        $base   = Carbon::parse($this->aten_rel_data);

        // Carbon 3: diffInDays() passou a retornar float com sinal por
        // padrão (antes, Carbon 2/Laravel 10 sempre retornava inteiro
        // absoluto). Passamos absolute:true e convertemos pra int aqui
        // pra manter o comportamento original desses cálculos de prazo.
        $total      = (int) $inicio->diffInDays($fim, true);
        $decorrido  = min((int) $inicio->diffInDays($base, true), $total);
        $aVencer    = max($total - $decorrido, 0);

        return [
            'prazo_total'      => $total,
            'prazo_decorrido'  => $decorrido,
            'prazo_a_vencer'   => $aVencer,
        ];
    }

    public function servicos()
    {
        return $this->hasMany(
            AtendimentoRelatorioServico::class,
            'aten_rel_serv_relatorio_id',
            'aten_rel_id'
        );
    }

    public function pecas()
    {
        return $this->hasMany(
            AtendimentoRelatorioPeca::class,
            'aten_rel_peca_relatorio_id',
            'aten_rel_id'
        );
    }

    public function ocorrencias()
    {
        return $this->belongsToMany(
            Ocorrencia::class,
            'atendimentos_relatorios_ocorrencias',
            'aten_rel_ocor_relatorio_id',
            'aten_rel_ocor_ocorrencia_id'
        )->withPivot([
            'aten_rel_ocor_id',
            'aten_rel_ocor_observacao',
        ]);
    }

    /**
     * RF001 — lista de itens de descrição (texto + foto opcional). Convive
     * com a coluna legada aten_rel_descricao sem migração de dado — ver
     * RF004: um relatório usa ou o campo legado, ou esta lista, nunca os
     * dois (decidido por qual dos dois tem registro).
     */
    public function itensDescricao()
    {
        // Ordena por ID de criação — a numeração do PDF ("1. ...", "2. ...")
        // depende da ordem de inclusão ser sempre a mesma em qualquer consumidor.
        return $this->hasMany(
            AtendimentoRelatorioDescricaoItem::class,
            'aten_rel_desc_relatorio_id',
            'aten_rel_id'
        )->orderBy('aten_rel_desc_id');
    }

    /**
     * NC02/NC03 — respostas às perguntas do modelo do Configurador
     * vinculado à natureza deste atendimento (BF04). Conceito novo,
     * independente do modeloRelatorio()/itensDescricao() legados.
     */
    public function respostas()
    {
        return $this->hasMany(
            AtendimentoRelatorioResposta::class,
            'aten_rel_resp_relatorio_id',
            'aten_rel_id'
        );
    }

}
