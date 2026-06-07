<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Relatório #{{ $relatorio->aten_rel_id }}</title>
    <style>
        @font-face {
            font-family: 'Poppins';
            src: url('{{ str_replace("\\", "/", public_path("fonts/Poppins/Poppins-Regular.ttf")) }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Poppins';
            src: url('{{ str_replace("\\", "/", public_path("fonts/Poppins/Poppins-Bold.ttf")) }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
            color: #222;
            background: #fff;
        }

        /* ── MARCA D'ÁGUA ── */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translateX(-50%) translateY(-50%);
            z-index: -1;
        }
        .watermark img { width: 700px; }

        /* ── CABEÇALHO ── */
        .header {
            text-align: center;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 2px solid #1a3c6e;
        }
        .header img.logo { height: 70px; }
        .header .titulo {
            margin-top: 8px;
            font-size: 14px;
            font-weight: bold;
            color: #1a3c6e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header .subtitulo {
            font-size: 10px;
            color: #555;
            margin-top: 2px;
        }

        /* ── RODAPÉ ── */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 9px;
            color: #888;
            border-top: 1px solid #ddd;
            padding: 5px 20px;
            text-align: center;
        }

        /* ── SEÇÕES ── */
        .section {
            margin-bottom: 16px;
        }
        .section-title {
            background-color: #1a3c6e;
            color: #fff;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 5px 10px;
            margin-bottom: 8px;
        }

        /* ── GRID DE CAMPOS ── */
        .field-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .field-grid td {
            padding: 4px 8px;
            vertical-align: top;
        }
        .field-label {
            font-weight: bold;
            color: #444;
            font-size: 9px;
            text-transform: uppercase;
            white-space: nowrap;
            width: 1%;
        }
        .field-value {
            color: #222;
            border-bottom: 1px solid #e0e0e0;
        }

        /* ── TABELAS ── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        .data-table thead tr {
            background-color: #eef2f8;
        }
        .data-table th {
            padding: 5px 8px;
            text-align: left;
            font-weight: bold;
            color: #1a3c6e;
            border: 1px solid #cdd6e8;
            font-size: 9px;
            text-transform: uppercase;
        }
        .data-table td {
            padding: 5px 8px;
            border: 1px solid #e0e0e0;
            vertical-align: top;
        }
        .data-table tbody tr:nth-child(even) { background-color: #f8f9fb; }

        /* ── CLIMA ── */
        .clima-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .clima-grid td {
            width: 33.33%;
            padding: 8px;
            border: 1px solid #e0e0e0;
            text-align: center;
            vertical-align: middle;
        }
        .clima-periodo {
            font-weight: bold;
            color: #1a3c6e;
            font-size: 10px;
            margin-bottom: 4px;
        }
        .clima-cond {
            font-size: 11px;
        }

        /* ── STATUS BADGES ── */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-info    { background-color: #17a2b8; color: #fff; }
        .badge-warning { background-color: #ffc107; color: #000; }
        .badge-success { background-color: #28a745; color: #fff; }

        /* ── ATIVIDADE STATUS ── */
        .status-0  { color: #888; }
        .status-1  { color: #17a2b8; }
        .status-2  { color: #007bff; }
        .status-3  { color: #28a745; }
        .status-4  { color: #dc3545; }
        .status-5  { color: #6c757d; }

        /* ── ASSINATURAS ── */
        .assinatura-box {
            width: 48%;
            display: inline-block;
            border: 1px solid #ccc;
            padding: 8px;
            margin: 4px;
            text-align: center;
            vertical-align: top;
        }
        .assinatura-box img {
            max-width: 100%;
            max-height: 80px;
        }
        .assinatura-title {
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            color: #444;
            margin-bottom: 6px;
        }
        .sem-assinatura {
            color: #aaa;
            font-size: 9px;
            height: 60px;
            line-height: 60px;
        }

        /* ── UTILITÁRIOS ── */
        .text-muted { color: #888; font-style: italic; }
        .pb-line    { border-bottom: 1px dashed #ccc; padding-bottom: 4px; margin-bottom: 4px; }
        .page-break { page-break-before: always; }
    </style>
</head>
@php
    $logoBase64   = base64_encode(file_get_contents(public_path('img/mcl_logo.jpeg')));
    $logoMime     = 'image/jpeg';
    $marcaBase64  = base64_encode(file_get_contents(public_path('img/mcl_marca.jpg')));
    $marcaMime    = 'image/jpeg';
@endphp
<body style="padding: 10px;">

{{-- MARCA D'ÁGUA --}}
<div class="watermark">
    <img src="data:{{ $marcaMime }};base64,{{ $marcaBase64 }}" alt="">
</div>

{{-- RODAPÉ --}}
<div class="footer">
    Relatório gerado em {{ now()->format('d/m/Y H:i') }} &nbsp;|&nbsp;
    {{ $relatorio->modeloRelatorio->mod_rel_descricao }} &nbsp;|&nbsp;
    Nº {{ $relatorio->aten_rel_id }}
</div>

{{-- CABEÇALHO --}}
<div class="header">
    <img class="logo" src="data:{{ $logoMime }};base64,{{ $logoBase64 }}" alt="Logo">
    <div class="titulo">{{ $relatorio->modeloRelatorio->mod_rel_descricao }}</div>
    <div class="subtitulo">
        Relatório de Atendimento &nbsp;|&nbsp; Nº {{ $relatorio->aten_rel_id }}
    </div>
</div>

{{-- ═══════════════ 1. DADOS ═══════════════ --}}
<div class="section">
    <div class="section-title">1. Dados Gerais</div>
    <table class="field-grid">
        <tr>
            <td class="field-label">Data</td>
            <td class="field-value">{{ $relatorio->aten_rel_data->format('d/m/Y') }}</td>
            <td class="field-label">Dia da Semana</td>
            <td class="field-value">{{ getFormatDiaSemana($relatorio->aten_rel_data) }}</td>
            <td class="field-label">Status</td>
            <td class="field-value">
                @php $statusLabels = [0 => ['Preenchendo','info'], 1 => ['Revisar','warning'], 2 => ['Aprovado','success']]; @endphp
                <span class="badge badge-{{ $statusLabels[$relatorio->aten_rel_status][1] ?? 'info' }}">
                    {{ $statusLabels[$relatorio->aten_rel_status][0] ?? '-' }}
                </span>
            </td>
        </tr>
        <tr>
            <td class="field-label">Prazo Total</td>
            <td class="field-value">{{ $prazoTotal }} dias</td>
            <td class="field-label">Prazo Decorrido</td>
            <td class="field-value">{{ $prazoDecorrido }} dias</td>
            <td class="field-label">Prazo à Vencer</td>
            <td class="field-value">{{ $prazoAVencer }} dias</td>
        </tr>
        <tr>
            <td class="field-label">Obra</td>
            <td class="field-value" colspan="3">{{ $relatorio->atendimento->aten_descricao }}</td>
            <td class="field-label">Nº Proposta</td>
            <td class="field-value">{{ $relatorio->atendimento->aten_nr_proposta ?? '-' }}</td>
        </tr>
        <tr>
            <td class="field-label">Endereço</td>
            <td class="field-value" colspan="5">{{ $relatorio->atendimento->aten_endereco ?? '-' }}</td>
        </tr>
        <tr>
            <td class="field-label">Cliente</td>
            <td class="field-value" colspan="3">{{ $relatorio->atendimento->cliente->cli_nome ?? '-' }}</td>
            <td class="field-label">Responsável</td>
            <td class="field-value">{{ $relatorio->atendimento->aten_responsavel ?? '-' }}</td>
        </tr>
        <tr>
            <td class="field-label">Setor</td>
            <td class="field-value" colspan="2">{{ $relatorio->atendimento->natureza?->tipoAtendimento?->tp_aten_descricao ?? '-' }}</td>
            <td class="field-label">Natureza</td>
            <td class="field-value" colspan="2">{{ $relatorio->atendimento->natureza?->nat_aten_descricao ?? '-' }}</td>
        </tr>
    </table>
</div>

{{-- ═══════════════ 2. HORÁRIOS ═══════════════ --}}
<div class="section">
    <div class="section-title">2. Horários</div>
    @php $h = $relatorio->horarios; @endphp
    @if($h)
        <table class="field-grid">
            <tr>
                <td class="field-label">Entrada</td>
                <td class="field-value">{{ $h->aten_rel_hora_entrada ? substr($h->aten_rel_hora_entrada, 0, 5) : '-' }}</td>
                <td class="field-label">Início Intervalo</td>
                <td class="field-value">{{ $h->aten_rel_hora_inicio_intervalo ? substr($h->aten_rel_hora_inicio_intervalo, 0, 5) : '-' }}</td>
                <td class="field-label">Fim Intervalo</td>
                <td class="field-value">{{ $h->aten_rel_hora_fim_intervalo ? substr($h->aten_rel_hora_fim_intervalo, 0, 5) : '-' }}</td>
                <td class="field-label">Saída</td>
                <td class="field-value">{{ $h->aten_rel_hora_saida ? substr($h->aten_rel_hora_saida, 0, 5) : '-' }}</td>
            </tr>
        </table>
    @else
        <span class="text-muted">Nenhum horário registrado.</span>
    @endif
</div>

{{-- ═══════════════ 3. CONDIÇÕES CLIMÁTICAS ═══════════════ --}}
<div class="section">
    <div class="section-title">3. Condições Climáticas</div>
    @php
        $condMap    = [1 => 'Ensolarado', 2 => 'Nublado', 3 => 'Chuvoso'];
        $periodoMap = [1 => 'Manhã', 2 => 'Tarde', 3 => 'Noite'];
        $climaOrg   = $relatorio->climas->keyBy('aten_rel_clima_periodo');
    @endphp
    <table class="clima-grid">
        <tr>
            @foreach([1, 2, 3] as $periodo)
            <td>
                <div class="clima-periodo">{{ $periodoMap[$periodo] }}</div>
                <div class="clima-cond">
                    @if(isset($climaOrg[$periodo]))
                        {{ $condMap[$climaOrg[$periodo]->aten_rel_clima_condicao] ?? '-' }}
                    @else
                        <span class="text-muted">Não informado</span>
                    @endif
                </div>
            </td>
            @endforeach
        </tr>
    </table>
</div>

{{-- ═══════════════ 4. MÃO DE OBRA ═══════════════ --}}
<div class="section">
    <div class="section-title">4. Mão de Obra</div>
    @if($relatorio->ocupacoes->isNotEmpty())
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Mão de Obra</th>
                    <th style="width:80px; text-align:center;">Qtd</th>
                </tr>
            </thead>
            <tbody>
                @foreach($relatorio->ocupacoes as $oc)
                <tr>
                    <td>{{ optional($oc->tipoOcupacao)->tp_ocup_descricao ?? '-' }}</td>
                    <td>{{ $oc->ocup_descricao }}</td>
                    <td style="text-align:center;">{{ $oc->pivot->aten_rel_ocup_quantidade }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <span class="text-muted">Nenhuma mão de obra registrada.</span>
    @endif
</div>

{{-- ═══════════════ 5. FERRAMENTAS / EQUIPAMENTOS ═══════════════ --}}
<div class="section">
    <div class="section-title">5. Ferramentas e Equipamentos</div>
    @if($relatorio->equipamentos->isNotEmpty())
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ferramenta / Equipamento</th>
                    <th style="width:80px; text-align:center;">Qtd</th>
                </tr>
            </thead>
            <tbody>
                @foreach($relatorio->equipamentos as $eq)
                <tr>
                    <td>{{ $eq->equip_descricao }}</td>
                    <td style="text-align:center;">{{ $eq->pivot->aten_rel_equip_quantidade }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <span class="text-muted">Nenhum equipamento registrado.</span>
    @endif
</div>

{{-- ═══════════════ 6. ATIVIDADES ═══════════════ --}}
<div class="section">
    <div class="section-title">6. Atividades</div>
    @php
        $atividadeStatus = [
            0 => 'Não iniciada',
            1 => 'Iniciada',
            2 => 'Em andamento',
            3 => 'Concluída',
            4 => 'Paralisada',
            5 => 'Não executada',
        ];
    @endphp
    @if($relatorio->atividades->isNotEmpty())
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Descrição</th>
                    <th style="width:120px;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($relatorio->atividades->sortBy('aten_rel_ativ_id') as $i => $atv)
                <tr>
                    <td style="width:30px; text-align:center;">{{ $i + 1 }}</td>
                    <td>{{ $atv->aten_rel_ativ_descricao }}</td>
                    <td class="status-{{ $atv->aten_rel_ativ_status }}">
                        {{ $atividadeStatus[$atv->aten_rel_ativ_status] ?? '-' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <span class="text-muted">Nenhuma atividade registrada.</span>
    @endif
</div>

{{-- ═══════════════ 7. OCORRÊNCIAS ═══════════════ --}}
<div class="section">
    <div class="section-title">7. Ocorrências</div>
    @if($relatorio->ocorrencias->isNotEmpty())
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ocorrência</th>
                    <th style="width:40%;">Observação</th>
                </tr>
            </thead>
            <tbody>
                @foreach($relatorio->ocorrencias as $oc)
                <tr>
                    <td>{{ $oc->ocor_descricao }}</td>
                    <td>{{ $oc->pivot->aten_rel_ocor_observacao ?: '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <span class="text-muted">Nenhuma ocorrência registrada.</span>
    @endif
</div>

{{-- ═══════════════ 8. COMENTÁRIOS ═══════════════ --}}
<div class="section">
    <div class="section-title">8. Comentários</div>
    @if($relatorio->comentarios->isNotEmpty())
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Comentário</th>
                </tr>
            </thead>
            <tbody>
                @foreach($relatorio->comentarios->sortBy('aten_rel_com_id') as $i => $com)
                <tr>
                    <td style="width:30px; text-align:center;">{{ $i + 1 }}</td>
                    <td>{{ $com->aten_rel_com_descricao }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <span class="text-muted">Nenhum comentário registrado.</span>
    @endif
</div>

{{-- ═══════════════ 9. ASSINATURAS ═══════════════ --}}
<div class="section page-break">
    <div class="section-title">9. Assinaturas</div>
    <div style="width:100%;">
        {{-- Responsável --}}
        <div class="assinatura-box">
            <div class="assinatura-title">Responsável</div>
            @php $assResp = $relatorio->assinaturaResponsavel(); @endphp
            @if($assResp && $assResp->aten_rel_ass_path)
                <img src="{{ storage_path('app/public/' . $assResp->aten_rel_ass_path) }}" alt="Assinatura Responsável">
            @else
                <div class="sem-assinatura">Não assinado</div>
            @endif
            <div style="border-top:1px solid #333; margin-top:8px; padding-top:4px; font-size:9px;">
                {{ $relatorio->atendimento->aten_responsavel ?? '' }}
            </div>
        </div>

        {{-- Cliente --}}
        <div class="assinatura-box">
            <div class="assinatura-title">Cliente</div>
            @php $assCli = $relatorio->assinaturaCliente(); @endphp
            @if($assCli && $assCli->aten_rel_ass_path)
                <img src="{{ storage_path('app/public/' . $assCli->aten_rel_ass_path) }}" alt="Assinatura Cliente">
            @else
                <div class="sem-assinatura">Não assinado</div>
            @endif
            <div style="border-top:1px solid #333; margin-top:8px; padding-top:4px; font-size:9px;">
                {{ $relatorio->atendimento->cliente->cli_nome ?? '' }}
            </div>
        </div>
    </div>
</div>

</body>
</html>
