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
        'aten_rel_data',
        'aten_rel_status',
        'aten_rel_descricao',
        'aten_rel_informacoes_adicionais',
        'aten_rel_dt_fim',
    ];

    protected $casts = [
        'aten_rel_data'   => 'date:Y-m-d',
        'aten_rel_status' => 'integer',
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

        $total      = $inicio->diffInDays($fim);
        $decorrido  = min($inicio->diffInDays($base), $total);
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
        return $this->hasMany(
            AtendimentoRelatorioDescricaoItem::class,
            'aten_rel_desc_relatorio_id',
            'aten_rel_id'
        );
    }

}
