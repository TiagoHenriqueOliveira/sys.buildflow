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
        }
        @font-face {
            font-family: 'Poppins';
            src: url('{{ str_replace("\\", "/", public_path("fonts/Poppins/Poppins-Bold.ttf")) }}') format('truetype');
            font-weight: bold;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            font-size: 18px;
            color: #222;
            background: #fff;
        }

        /* MARCA D'ÁGUA */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translateX(-50%) translateY(-50%);
            z-index: -1;
        }
        .watermark img { width: 700px; }

        /* RODAPÉ */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #ddd;
            padding: 4px 20px;
            text-align: center;
        }

        /* CABEÇALHO */
        .header { width: 100%; border-collapse: collapse; margin-bottom: 20px; border-bottom: 2px solid #00552a; padding-bottom: 18px; }
        .header-logo { width: 140px; vertical-align: middle; padding-bottom: 10px; }
        .header-logo img { height: 60px; }
        .header-info { vertical-align: middle; text-align: center; }
        .header-titulo { font-size: 20px; font-weight: bold; color: #00552a; text-transform: uppercase; letter-spacing: 0.5px; }
        .header-nr { width: 100px; vertical-align: middle; text-align: right; }
        .header-nr .nr-label { font-size: 10px; color: #888; text-transform: uppercase; }
        .header-nr .nr-valor { font-size: 12px; font-weight: bold; color: #00552a; }

        /* SEÇÕES */
        .section { margin-bottom: 14px; }
        .section-title {
            background-color: #00552a;
            color: #fff;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 4px 10px;
            margin-bottom: 7px;
        }

        /* GRID DE CAMPOS */
        .field-grid { width: 100%; border-collapse: collapse; }
        .field-grid td { padding: 3px 7px; vertical-align: top; }
        .field-label { font-weight: bold; color: #555; font-size: 18px; text-transform: uppercase; white-space: nowrap; width: 1%; }
        .field-value { color: #222; font-size: 20px; border-bottom: 1px solid #eee; }

        /* TABELAS */
        .data-table { width: 100%; border-collapse: collapse; font-size: 20px; }
        .data-table thead tr { background-color: #eef2f8; }
        .data-table th { padding: 4px 8px; text-align: left; font-weight: bold; color: #00552a; border: 1px solid #cdd6e8; font-size: 20px; text-transform: uppercase; }
        .data-table td { padding: 4px 8px; border: 1px solid #e0e0e0; vertical-align: top; }
        .data-table tbody tr:nth-child(even) { background-color: #f8f9fb; }

        /* TEXTO LIVRE */
        .text-block { border: 1px solid #e0e0e0; border-radius: 2px; padding: 8px 10px; font-size: 20px; color: #333; min-height: 30px; white-space: pre-wrap; }

        /* DESCRIÇÃO — itens (RF001): no máximo 1 foto por item, o texto é um
           comentário dela. Uma linha por item: foto (quando houver) numa
           coluna fixa à esquerda, texto ao lado ocupando o resto da linha,
           com 15px de espaço entre os dois. Item sem foto: texto sozinho
           ocupando a linha inteira (colspan), sem reservar o espaço da
           foto. Decisão explícita do usuário em 02/09/2026 — essa
           disposição (foto e texto lado a lado, nunca em linhas/células
           separadas que precisem quebrar página junto) foi a única testada
           nesta sessão que nunca corrompeu, mesmo com comentários muito
           longos (~1500 caracteres). */
        .section-descricao .section-title { margin-bottom: 18px; }
        .descricao-grid { width: 100%; border-collapse: collapse; }
        .descricao-grid td { padding: 0 0 22px 0; text-align: left; vertical-align: top; }
        .descricao-grid td.descricao-foto-col { width: 260px; padding-right: 15px; }
        .descricao-foto { width: 260px; height: 326px; border: 1px solid #ddd; display: block; }
        .descricao-texto-only { font-size: 20px; color: #333; white-space: pre-wrap; text-align: justify; }

        /* FOTOS */
        .fotos-grid { width: 100%; border-collapse: collapse; }
        .fotos-grid td { padding: 8px; text-align: center; vertical-align: top; width: 50%; }
        .fotos-grid img { width: 380px; height: 500px; border: 1px solid #ddd; }
        .foto-legenda { font-size: 10px; color: #888; margin-top: 4px; }

        /* ASSINATURAS */
        .assinaturas-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .assinatura-cell { width: 50%; border: 1px solid #ccc; padding: 10px; text-align: center; vertical-align: top; }
        .assinatura-title { font-weight: bold; font-size: 10px; text-transform: uppercase; color: #444; margin-bottom: 8px; }
        .assinatura-cell img { max-width: 100%; max-height: 80px; }
        .sem-assinatura { color: #bbb; font-size: 10px; height: 60px; line-height: 60px; }
        .assinatura-nome { border-top: 1px solid #333; margin-top: 8px; padding-top: 4px; font-size: 10px; color: #555; }
        .assinatura-assinante { font-size: 10px; color: #555; margin-top: 3px; }
        .assinatura-data { font-size: 10px; color: #888; margin-top: 3px; }

        /* UTILITÁRIOS */
        .text-muted { color: #aaa; font-style: italic; font-size: 14px; }
        .page-break  { page-break-before: always; }
    </style>
</head>
@php
    $logoBase64  = base64_encode(file_get_contents(public_path('img/mcl_logo.png')));
    $marcaBase64 = base64_encode(file_get_contents(public_path('img/mcl_marca.jpg')));

    $climaOrg = $relatorio->climas->keyBy('aten_rel_clima_periodo');
    $condMap  = [1 => 'Ensolarado', 2 => 'Nublado', 3 => 'Chuvoso'];

    $assResp = $relatorio->assinaturas->first(fn($a) => $a->aten_rel_ass_tipo->value === 'responsavel');
    $assCli  = $relatorio->assinaturas->first(fn($a) => $a->aten_rel_ass_tipo->value === 'cliente');

    $imgBase64 = function(string $path): string {
        $full = storage_path('app/public/' . ltrim($path, '/'));
        if (!file_exists($full)) return '';
        $mime = mime_content_type($full) ?: 'image/jpeg';
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($full));
    };

    // Corrige a orientação (fotos tiradas em retrato mas gravadas em paisagem) e
    // padroniza todas as fotos para o mesmo tamanho de exibição na grade do relatório.
    $fotoBase64 = function(string $path): string {
        $full = storage_path('app/public/' . ltrim($path, '/'));
        if (!file_exists($full)) return '';

        $info = @getimagesize($full);
        if (!$info) return '';

        $src = match ($info['mime'] ?? null) {
            'image/jpeg' => @imagecreatefromjpeg($full),
            'image/png'  => @imagecreatefrompng($full),
            'image/gif'  => @imagecreatefromgif($full),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($full) : null,
            default      => null,
        };
        if (!$src) return '';

        // Respeita a orientação EXIF (comum em fotos de celular) quando disponível.
        $rotated = false;
        if (($info['mime'] ?? null) === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($full);
            $orientation = $exif['Orientation'] ?? 1;
            if (in_array($orientation, [3, 6, 8], true)) {
                $src = imagerotate($src, match ($orientation) {
                    3       => 180,
                    6       => -90,
                    8       => 90,
                    default => 0,
                }, 0);
                $rotated = true;
            }
        }

        // Sem EXIF de orientação: se a imagem ficou em paisagem, força retrato.
        if (!$rotated && imagesx($src) > imagesy($src)) {
            $src = imagerotate($src, -90, 0);
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);

        // Corta (cover) para um tamanho fixo, garantindo que todas as fotos
        // fiquem com a mesma largura/altura na grade, mesmo com proporções diferentes.
        $targetW = 380;
        $targetH = 500;
        $scale   = max($targetW / $srcW, $targetH / $srcH);
        $cropW   = (int) round($targetW / $scale);
        $cropH   = (int) round($targetH / $scale);
        $cropX   = (int) (($srcW - $cropW) / 2);
        $cropY   = (int) (($srcH - $cropH) / 2);

        $dst = imagecreatetruecolor($targetW, $targetH);
        imagecopyresampled($dst, $src, 0, 0, $cropX, $cropY, $targetW, $targetH, $cropW, $cropH);
        imagedestroy($src);

        ob_start();
        imagejpeg($dst, null, 85);
        $data = ob_get_clean();
        imagedestroy($dst);

        return 'data:image/jpeg;base64,' . base64_encode($data);
    };

    // Numeração sequencial das seções: só conta as que realmente aparecem no relatório.
    $secCount = 0;
    $secNum   = function() use (&$secCount) { return ++$secCount; };
@endphp
<body style="padding: 45px 80px 45px 80px;">

{{-- MARCA D'ÁGUA --}}
<div class="watermark">
    <img src="data:image/jpeg;base64,{{ $marcaBase64 }}" alt="">
</div>

{{-- RODAPÉ --}}
<div class="footer">
    Gerado em {{ now()->format('d/m/Y \à\s H:i') }}
    &nbsp;|&nbsp; {{ $relatorio->modeloRelatorio->mod_rel_descricao }}
    &nbsp;|&nbsp; Nº {{ $relatorio->aten_rel_id }}
</div>

{{-- CABEÇALHO --}}
<table class="header" width="100%">
    <tr>
        <td class="header-logo">
            <img src="data:image/jpeg;base64,{{ $logoBase64 }}" alt="Logo MCL">
        </td>
        <td class="header-info">
            <div class="header-titulo">{{ $relatorio->modeloRelatorio->mod_rel_descricao }}</div>
        </td>
        <td class="header-nr">
            <div class="nr-label">Nº Relatório</div>
            <div class="nr-valor">#{{ $relatorio->aten_rel_id }}</div>
        </td>
    </tr>
</table>

{{-- 1. DADOS DO ATENDIMENTO --}}
<div class="section">
    <div class="section-title">{{ $secNum() }}. Dados do Atendimento</div>
    <table class="field-grid">
        <tr>
            <td class="field-label">Cliente</td>
            <td class="field-value" colspan="3">{{ $relatorio->atendimento->cliente->cli_nome ?? '-' }}</td>
            <td class="field-label">Nº Proposta</td>
            <td class="field-value">{{ $relatorio->atendimento->aten_nr_proposta ?? '-' }}</td>
        </tr>
        <tr>
            <td class="field-label">Natureza</td>
            <td class="field-value" colspan="3">{{ $relatorio->atendimento->natureza?->nat_aten_descricao ?? '-' }}</td>
            <td class="field-label">Técnico</td>
            <td class="field-value">{{ $relatorio->atendimento->usuario?->user_nome ?? '-' }}</td>
        </tr>
        <tr>
            <td class="field-label">Endereço</td>
            <td class="field-value" colspan="5">{{ $relatorio->atendimento->aten_endereco ?? '-' }}</td>
        </tr>
        <tr>
            <td class="field-label">Responsável</td>
            <td class="field-value" colspan="3">{{ $relatorio->atendimento->aten_responsavel ?? '-' }}</td>
            <td class="field-label">Telefone</td>
            <td class="field-value">{{ $relatorio->atendimento->aten_telefone ?? '-' }}</td>
        </tr>
        <tr>
            <td class="field-label">Período</td>
            <td class="field-value">
                {{ $relatorio->atendimento->aten_dt_inicio?->format('d/m/Y') ?? '-' }}
                &nbsp;–&nbsp;
                {{ $relatorio->atendimento->aten_dt_fim?->format('d/m/Y') ?? '-' }}
            </td>
            <td class="field-label">Data do Relatório</td>
            <td class="field-value">{{ optional($relatorio->aten_rel_data)->format('d/m/Y') ?? '-' }}</td>
            <td class="field-label">Prazo Restante</td>
            <td class="field-value">{{ $prazoAVencer }} dias</td>
        </tr>
    </table>
</div>

{{-- 2. OBSERVAÇÕES --}}
@if($relatorio->atendimento->aten_obs_cliente)
<div class="section">
    <div class="section-title">{{ $secNum() }}. Observações</div>
    <div class="text-block">{{ $relatorio->atendimento->aten_obs_cliente }}</div>
</div>
@endif

{{-- 3. EQUIPAMENTOS — só aparece se houver equipamento (ajuste fora do escopo,
     mudança geral: seção some inteira em vez de mostrar placeholder vazio). --}}
@php $equips = $relatorio->atendimento->equipamentos; @endphp
@if($equips->isNotEmpty())
<div class="section">
    <div class="section-title">{{ $secNum() }}. Equipamentos</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Descrição</th>
            </tr>
        </thead>
        <tbody>
            @foreach($equips as $i => $eq)
            <tr>
                <td style="width:30px; text-align:center;">{{ $i + 1 }}</td>
                <td>{{ $eq->aten_equip_descricao }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- 4. HORÁRIO — só aparece se houver registro de horário. --}}
@php $h = $relatorio->horarios; @endphp
@if($h)
<div class="section">
    <div class="section-title">{{ $secNum() }}. Horário</div>
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
</div>
@endif

{{-- 5. DESCRIÇÃO — RF001/RF004: relatório usa OU a lista nova de itens
     (texto + foto opcional), OU o campo legado de texto único; nunca os
     dois juntos. O critério é a existência de item na tabela nova. --}}
@if($relatorio->itensDescricao->isNotEmpty())
<div class="section section-descricao">
    <div class="section-title">{{ $secNum() }}. Descrição</div>
    <table class="descricao-grid">
        @foreach($relatorio->itensDescricao as $item)
            @php $itemFotoSrc = $item->fotos->isNotEmpty() ? $fotoBase64($item->fotos->first()->aten_rel_desc_foto_path) : ''; @endphp
            <tr>
                @if($itemFotoSrc !== '')
                    <td class="descricao-foto-col"><img class="descricao-foto" src="{{ $itemFotoSrc }}" alt="Foto do item"></td>
                    <td><div class="descricao-texto-only">{{ $item->aten_rel_desc_texto }}</div></td>
                @else
                    <td colspan="2"><div class="descricao-texto-only">{{ $item->aten_rel_desc_texto }}</div></td>
                @endif
            </tr>
        @endforeach
    </table>
</div>
@elseif($relatorio->aten_rel_descricao)
<div class="section">
    <div class="section-title">{{ $secNum() }}. Descrição</div>
    <div class="text-block">{{ $relatorio->aten_rel_descricao }}</div>
</div>
@endif

{{-- 6. SERVIÇOS PRESTADOS — só aparece se houver serviço. --}}
@if($relatorio->servicos->isNotEmpty())
<div class="section">
    <div class="section-title">{{ $secNum() }}. Serviços Prestados</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Serviço</th>
            </tr>
        </thead>
        <tbody>
            @foreach($relatorio->servicos as $i => $s)
            <tr>
                <td style="width:30px; text-align:center;">{{ $i + 1 }}</td>
                <td>{{ $s->aten_rel_serv_descricao }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- 7. PEÇAS SUBSTITUÍDAS — só aparece se houver peça. --}}
@if($relatorio->pecas->isNotEmpty())
<div class="section">
    <div class="section-title">{{ $secNum() }}. Peças Substituídas</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Peça</th>
            </tr>
        </thead>
        <tbody>
            @foreach($relatorio->pecas as $i => $p)
            <tr>
                <td style="width:30px; text-align:center;">{{ $i + 1 }}</td>
                <td>{{ $p->aten_rel_peca_descricao }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- 8. OCORRÊNCIAS — só aparece se houver ocorrência. --}}
@if($relatorio->ocorrencias->isNotEmpty())
<div class="section">
    <div class="section-title">{{ $secNum() }}. Ocorrências</div>
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
</div>
@endif

{{-- 9. OBSERVAÇÕES GERAIS (RF002 — rótulo; campo/coluna inalterados) --}}
@if($relatorio->aten_rel_informacoes_adicionais)
<div class="section">
    <div class="section-title">{{ $secNum() }}. Observações Gerais</div>
    <div class="text-block">{{ $relatorio->aten_rel_informacoes_adicionais }}</div>
</div>
@endif

{{-- OBSERVAÇÕES TÉCNICAS E DE MANUTENÇÃO (fora do escopo original — ajuste
     pontual pedido à parte). Campos do atendimento, só aparecem com
     conteúdo, posicionados antes de Entrega Técnica. --}}
@if($relatorio->atendimento->aten_obs_tecnica)
<div class="section">
    <div class="section-title">{{ $secNum() }}. Observações Técnicas</div>
    <div class="text-block">{{ $relatorio->atendimento->aten_obs_tecnica }}</div>
</div>
@endif

@if($relatorio->atendimento->aten_obs_manutencao)
<div class="section">
    <div class="section-title">{{ $secNum() }}. Observações de Manutenção</div>
    <div class="text-block">{{ $relatorio->atendimento->aten_obs_manutencao }}</div>
</div>
@endif

{{-- ENTREGA TÉCNICA (condicional) --}}
@if($relatorio->atendimento->aten_entrega_tecnica)
@php
    $equipNomes = $relatorio->atendimento->equipamentos->pluck('aten_equip_descricao');
    $equipStr   = $equipNomes->count()
        ? $equipNomes->slice(0, -1)->implode(' ; ')
            . ($equipNomes->count() > 1 ? ' ; ' : '')
            . $equipNomes->last() . '.'
        : '-';
@endphp
<div class="section">
    <div class="section-title">{{ $secNum() }}. Entrega Técnica</div>
    <p style="font-weight:bold; margin-bottom:6px;">Entrega Técnica de Equipamentos</p>
    <p style="margin-bottom:6px;"><strong>Equipamento instalado:</strong> {{ $equipStr }}</p>
    <p style="margin-bottom:4px;">A MCLvale, neste ato representado pelo técnico abaixo assinado, coloca em início a operação e funcionamento do(s) equipamento(s) adquirido(s).</p>
    <p style="margin-bottom:4px;">A equipe operacional foi devidamente instruída, tendo recebido todas as informações necessárias para o correto funcionamento dos equipamentos/instalação.</p>
    <p style="margin-bottom:4px;">Além dessas informações, é indispensável a leitura do manual de Fabricação do Equipamento.</p>
    <p style="margin-bottom:4px;"><em>OBS: Esse registro é utilizado para controle técnico.</em></p>
    <p>A garantia dos equipamentos permanece em vigor, de acordo com as "condições gerais de venda".</p>
</div>
@endif

{{-- ASSINATURAS --}}
<div class="section">
    <div class="section-title">{{ $secNum() }}. Assinaturas</div>
    <table class="assinaturas-table">
        <tr>
            <td class="assinatura-cell">
                <div class="assinatura-title">Técnico</div>
                @if($assResp && $assResp->aten_rel_ass_path)
                    <img src="{{ $imgBase64($assResp->aten_rel_ass_path) }}" alt="Assinatura Técnico">
                @else
                    <div class="sem-assinatura">Não assinado</div>
                @endif
                {{-- Nome/CPF só são exigidos para o cliente; técnico usa o nome do
                     usuário atribuído ao atendimento. Data/hora abaixo do nome. --}}
                <div class="assinatura-nome">{{ optional($assResp)->aten_rel_ass_nome ?? $relatorio->atendimento->usuario?->user_nome ?? '' }}</div>
                @if($assResp && $assResp->aten_rel_ass_assinado_em)
                    <div class="assinatura-data">{{ $assResp->aten_rel_ass_assinado_em->format('d/m/Y H:i') }}</div>
                @endif
            </td>
            <td class="assinatura-cell">
                <div class="assinatura-title">Cliente</div>
                @if($assCli && $assCli->aten_rel_ass_path)
                    <img src="{{ $imgBase64($assCli->aten_rel_ass_path) }}" alt="Assinatura Cliente">
                @else
                    <div class="sem-assinatura">Não assinado</div>
                @endif
                {{-- Nome do cliente cadastrado (sempre) → nome e CPF de quem
                     efetivamente assinou (só depois de assinado) → data/hora
                     por último, abaixo do CPF. --}}
                <div class="assinatura-nome">{{ optional($relatorio->atendimento->cliente)->cli_nome }}</div>
                @if($assCli && $assCli->aten_rel_ass_nome)
                    <div class="assinatura-assinante">{{ $assCli->aten_rel_ass_nome }}</div>
                @endif
                @if($assCli && $assCli->aten_rel_ass_cpf)
                    <div class="assinatura-data">CPF: {{ $assCli->aten_rel_ass_cpf }}</div>
                @endif
                @if($assCli && $assCli->aten_rel_ass_assinado_em)
                    <div class="assinatura-data">{{ $assCli->aten_rel_ass_assinado_em->format('d/m/Y H:i') }}</div>
                @endif
            </td>
        </tr>
    </table>
</div>

{{-- FOTOS — só mostra a seção se pelo menos uma foto realmente existir no
     disco e renderizar; ter registro no banco não basta (arquivo pode estar
     ausente/corrompido), senão sobra título sem nenhuma foto embaixo. --}}
@php
    $fotosRenderaveis = $relatorio->fotos->map(fn($foto) => [
        'foto' => $foto,
        'src'  => $fotoBase64($foto->aten_rel_foto_path),
    ])->filter(fn($item) => $item['src'] !== '')->values();
@endphp
@if($fotosRenderaveis->isNotEmpty())
<div class="section page-break">
    <div class="section-title">{{ $secNum() }}. Fotos</div>
    @php $fotos = $fotosRenderaveis->chunk(2); @endphp
    <table class="fotos-grid">
        @foreach($fotos as $par)
        <tr>
            @foreach($par as $item)
            <td @if($par->count() < 2) colspan="2" @endif>
                <img src="{{ $item['src'] }}" alt="Foto">
                @if($item['foto']->aten_rel_foto_legenda)
                    <div class="foto-legenda">{{ $item['foto']->aten_rel_foto_legenda }}</div>
                @endif
            </td>
            @endforeach
        </tr>
        @endforeach
    </table>
</div>
@endif

</body>
</html>
