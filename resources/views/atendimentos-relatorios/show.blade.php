{{-- Tela de detalhe do relatório — a mais complexa da migração pro
     sbadmin/dashboard (ver CLAUDE.md, seção "Template visual"): 10 abas,
     cada uma com seu próprio <form> ou lista dinâmica, salvando via fetch()
     pros MESMOS endpoints JSON de sempre (update-dados, update-horarios,
     update-clima, update-assinaturas, texto, ocorrencias, servicos, pecas,
     descricao-itens, upload-anexos, anexos) — nada de persistência mudou
     aqui, só a casca visual (Bootstrap4/jQuery -> Bootstrap5/Alpine) e a
     forma de trocar de aba (Bootstrap tabs -> x-show). O botão único
     "Atualizar" no rodapé despacha pra aba ativa, replicando
     initAtualizarRelatorio() do antigo public/js/app/atendimentos.relatorios.js
     (removido nesta migração). O PDF (BF06) e pdf.blade.php NÃO foram
     tocados — são renderizados fora do template sbadmin, sem jQuery. --}}
<x-layout title="Visualizar Relatório">
    <div
        id="relatorio-root"
        data-relatorio-id="{{ $atendimentoRelatorio->aten_rel_id }}"
        x-data="{ tab: 'dados' }"
    >
        @php
            // Pedido do cliente (2026-09-11): sem mais checklist de secoes por
            // modelo. Dados/Horarios/Anexos/Observacoes Gerais/Assinatura sao
            // SEMPRE fixos; Clima/Servicos/Pecas/Ocorrencias so aparecem se o
            // relatorio ja tiver dado legado (tabelas antigas, ainda usadas
            // pelo app mobile atual) - relatorio NOVO usa a aba Perguntas pra
            // tudo isso, ver project_fae_bioenergia na memoria.
            $temDescricaoLegado = $atendimentoRelatorio->itensDescricao->isNotEmpty() || $atendimentoRelatorio->aten_rel_descricao;
            $temClimaLegado = $atendimentoRelatorio->climas->isNotEmpty();
            $temServicosLegado = $atendimentoRelatorio->servicos->isNotEmpty();
            $temPecasLegado = $atendimentoRelatorio->pecas->isNotEmpty();
            $temOcorrenciasLegado = $atendimentoRelatorio->ocorrencias->isNotEmpty();
            $temPerguntas = (bool) $atendimentoRelatorio->configModelo?->perguntas->isNotEmpty();

            $abas = ['dados' => 'Dados', 'horarios' => 'Horário'];
            if ($temClimaLegado) $abas['clima'] = 'Clima';
            if ($temDescricaoLegado) $abas['descricao'] = 'Descrição';
            if ($temPerguntas) $abas['perguntas'] = 'Perguntas';
            if ($temServicosLegado) $abas['servicos'] = 'Serviços Prestados';
            if ($temPecasLegado) $abas['pecas'] = 'Peças Substituídas';
            if ($temOcorrenciasLegado) $abas['ocorrencias'] = 'Ocorrências';
            $abas['info-adicionais'] = 'Observações Gerais';
            $abas['anexos'] = 'Anexos';
            $abas['observacao-interna'] = 'Observação Interna';
            $abas['compartilhamento'] = 'Compartilhamento';
            $abas['assinatura'] = 'Assinatura';
        @endphp

        <div class="sbadmin-page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h2 class="sbadmin-page-heading">{{ $atendimentoRelatorio->configModelo->cfg_mod_nome ?? $atendimentoRelatorio->modeloRelatorio->mod_rel_descricao }}</h2>
                <p class="sbadmin-page-subheading">Relatório de atendimento #{{ $atendimentoRelatorio->aten_rel_id }}</p>
            </div>
        </div>

        @if(session('success'))
            <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
        @endif

        {{-- BF10 - bloqueio VISUAL (Etapa 1/Telas) de edicao apos aprovacao;
             regra de negocio fina/servidor fica pra Etapa 2 (Persistencia),
             ver docs/cronograma/08-web-atendimento-telas.md. --}}
        @if($somenteLeitura)
            <div class="sbadmin-alert sbadmin-alert-info mb-3" role="alert">
                <i class="bi bi-lock-fill" aria-hidden="true"></i>
                Este relatório já foi <strong>aprovado</strong> e está somente para leitura.
                @if($atendimentoRelatorio->aprovadoPor)
                    Aprovado por {{ $atendimentoRelatorio->aprovadoPor->user_nome }} em {{ $atendimentoRelatorio->aten_rel_aprovado_em?->format('d/m/Y H:i') }}.
                @endif
            </div>
        @endif

        <div id="relatorio-feedback"></div>

        <div class="sbadmin-card" @if($somenteLeitura) id="relatorio-somente-leitura" @endif>
            <div class="sbadmin-card-body">
                <ul class="nav nav-tabs mb-3 flex-nowrap overflow-x-auto overflow-y-hidden" role="tablist">
                    @foreach($abas as $key => $label)
                        <li class="nav-item text-nowrap">
                            <button
                                type="button"
                                class="nav-link"
                                :class="{ active: tab === '{{ $key }}' }"
                                @click="tab = '{{ $key }}'; carregarAbaRelatorio('{{ $key }}')"
                            >{{ $label }}</button>
                        </li>
                    @endforeach
                </ul>

                <fieldset @if($somenteLeitura) disabled @endif>
                    @include('atendimentos-relatorios.tabs.dados')
                    @include('atendimentos-relatorios.tabs.horarios')
                    @if($temClimaLegado) @include('atendimentos-relatorios.tabs.clima') @endif
                    @if($temDescricaoLegado) @include('atendimentos-relatorios.tabs.descricao') @endif
                    @if($temPerguntas) @include('atendimentos-relatorios.tabs.perguntas') @endif
                    @if($temServicosLegado) @include('atendimentos-relatorios.tabs.servicos-prestados') @endif
                    @if($temPecasLegado) @include('atendimentos-relatorios.tabs.pecas-substituidas') @endif
                    @if($temOcorrenciasLegado) @include('atendimentos-relatorios.tabs.ocorrencias') @endif
                    @include('atendimentos-relatorios.tabs.informacoes-adicionais')
                    @include('atendimentos-relatorios.tabs.anexos')
                    @include('atendimentos-relatorios.tabs.observacao-interna')
                    @include('atendimentos-relatorios.tabs.compartilhamento')
                    @include('atendimentos-relatorios.tabs.assinaturas')
                </fieldset>
            </div>

            <div class="sbadmin-card-body d-flex justify-content-end gap-2 border-top">
                @unless($somenteLeitura)
                    <button type="button" id="btnAtualizarRelatorio" class="btn btn-success">
                        <i class="bi bi-check-lg" aria-hidden="true"></i> Atualizar
                    </button>
                @endunless
                <a href="{{ route('atendimentos-relatorios.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Voltar
                </a>
            </div>
        </div>
    </div>

    {{-- Modal de preview de anexo (foto/vídeo) — compartilhado pelas abas
         Anexos e Descrição. --}}
    <div class="modal-backdrop show" x-show="previewAberto" x-cloak x-data></div>
    <div
        class="modal"
        :class="{ show: previewAberto }"
        :style="previewAberto ? 'display: block' : 'display: none'"
        x-cloak
        x-data="{ previewAberto: false }"
        x-init="window.addEventListener('relatorio-preview', () => previewAberto = true)"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        @keydown.escape.window="previewAberto = false"
        @click.self="previewAberto = false"
    >
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" aria-label="Fechar" @click="previewAberto = false"></button>
                </div>
                <div class="modal-body text-center" id="anexoPreviewContent"></div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const RELATORIO_ID = document.getElementById('relatorio-root').dataset.relatorioId;
            const RELATORIOS_BASE_URL = '{{ url('/atendimentos-relatorios') }}';

            function csrfToken() {
                return document.querySelector('meta[name="csrf-token"]')?.content || '';
            }

            function escapeHtml(str) {
                return String(str ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function mostrarFeedbackRelatorio(tipo, mensagem) {
                const container = document.getElementById('relatorio-feedback');
                if (!container) return;
                const classe = tipo === 'success' ? 'sbadmin-alert-success' : 'sbadmin-alert-error';
                const icone = tipo === 'success' ? 'bi-check-circle-fill' : 'bi-x-circle-fill';
                container.innerHTML = `<div class="sbadmin-alert ${classe}" role="alert">
                    <i class="bi ${icone} sbadmin-alert-icon" aria-hidden="true"></i>
                    <div class="sbadmin-alert-content">${mensagem}</div>
                </div>`;
                window.setTimeout(() => { container.innerHTML = ''; }, 4000);
            }

            function mostrarErroAjax(xhr, payload) {
                if (xhr.status === 422) {
                    const errors = payload?.errors || {};
                    const keys = Object.keys(errors);
                    if (keys.length) {
                        mostrarFeedbackRelatorio('error', Object.values(errors).flat().join('<br>'));
                        return;
                    }
                    mostrarFeedbackRelatorio('error', payload?.message || 'Erro de validação.');
                    return;
                }
                mostrarFeedbackRelatorio('error', payload?.message || 'Ops... um erro inesperado ocorreu!');
            }

            async function fetchJson(url, options = {}) {
                const response = await fetch(url, {
                    ...options,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'Accept': 'application/json',
                        ...(options.headers || {}),
                    },
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const err = new Error(payload?.message || 'Erro');
                    err.status = response.status;
                    err.payload = payload;
                    throw err;
                }
                return payload;
            }

            function abrirPreview(tipo, src) {
                const container = document.getElementById('anexoPreviewContent');
                container.innerHTML = '';
                if (tipo === 'image') {
                    const img = document.createElement('img');
                    img.src = src;
                    img.style.maxWidth = '100%';
                    img.style.height = 'auto';
                    container.appendChild(img);
                } else if (tipo === 'video') {
                    const video = document.createElement('video');
                    video.controls = true;
                    video.playsInline = true;
                    video.style.width = '100%';
                    video.style.height = 'auto';
                    const source = document.createElement('source');
                    source.src = src;
                    video.appendChild(source);
                    container.appendChild(video);
                }
                window.dispatchEvent(new CustomEvent('relatorio-preview'));
            }

            document.addEventListener('click', function (event) {
                const thumb = event.target.closest('.anexo-thumb');
                if (thumb) {
                    event.preventDefault();
                    abrirPreview(thumb.dataset.type, thumb.dataset.src);
                }

                const uploadTrigger = event.target.closest('.upload-trigger');
                if (uploadTrigger) {
                    document.getElementById(uploadTrigger.dataset.inputId)?.click();
                }
            });

            document.addEventListener('change', function (event) {
                if (!event.target.classList.contains('file-upload-input')) return;
                const files = event.target.files;
                const label = event.target.closest('.file-upload-group')?.querySelector('.file-upload-text');
                if (!label) return;
                label.textContent = files && files.length
                    ? Array.from(files).map((f) => f.name).join(', ')
                    : 'Nenhum arquivo selecionado';
            });

            // ─── Dados ──────────────────────────────────────────────────────────
            function carregarDadosRelatorio() {
                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/dados`).then((d) => {
                    document.querySelector('input[name="aten_rel_data"]').value = d.aten_rel_data_iso;
                    document.getElementById('dia_semana_relatorio').value = d.dia_semana;
                });
            }

            // ─── Horários ───────────────────────────────────────────────────────
            function carregarHorariosRelatorio() {
                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/horarios`).then((h) => {
                    document.querySelector('input[name="aten_rel_hora_entrada"]').value = h.entrada;
                    document.querySelector('input[name="aten_rel_hora_inicio_intervalo"]').value = h.inicio_intervalo;
                    document.querySelector('input[name="aten_rel_hora_fim_intervalo"]').value = h.fim_intervalo;
                    document.querySelector('input[name="aten_rel_hora_saida"]').value = h.saida;
                });
            }

            // ─── Clima ──────────────────────────────────────────────────────────
            function carregarClimaRelatorio() {
                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/clima`).then((clima) => {
                    if (clima?.manha) document.getElementById(`manha_${clima.manha}`)?.setAttribute('checked', 'checked');
                    if (clima?.tarde) document.getElementById(`tarde_${clima.tarde}`)?.setAttribute('checked', 'checked');
                    if (clima?.noite) document.getElementById(`noite_${clima.noite}`)?.setAttribute('checked', 'checked');
                });
            }

            // ─── Observações Gerais ─────────────────────────────────────────────
            document.getElementById('form_informacoes_adicionais')?.addEventListener('submit', function (event) {
                event.preventDefault();
                const valor = document.getElementById('aten_rel_informacoes_adicionais').value;
                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/texto/aten_rel_informacoes_adicionais`, {
                    method: 'POST',
                    body: new URLSearchParams({ valor }),
                })
                    .then(() => mostrarFeedbackRelatorio('success', 'Observações Gerais salvo com sucesso.'))
                    .catch((err) => mostrarErroAjax({ status: err.status }, err.payload));
            });

            // ─── Observação Interna (BF11 - nunca aparece no PDF assinado) ─────
            document.getElementById('form_observacao_interna')?.addEventListener('submit', function (event) {
                event.preventDefault();
                const valor = document.getElementById('aten_rel_observacao_interna').value;
                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/texto/aten_rel_observacao_interna`, {
                    method: 'POST',
                    body: new URLSearchParams({ valor }),
                })
                    .then(() => mostrarFeedbackRelatorio('success', 'Observação interna salva com sucesso.'))
                    .catch((err) => mostrarErroAjax({ status: err.status }, err.payload));
            });

            // ─── Serviços ───────────────────────────────────────────────────────
            function renderizarItensTabela(items, tbodySelector, campo, removerClasse, label) {
                const tbody = document.querySelector(tbodySelector);
                tbody.innerHTML = '';
                if (!items || !items.length) {
                    tbody.innerHTML = `<tr><td colspan="2" class="text-center text-muted">Nenhum ${label} cadastrado.</td></tr>`;
                    return;
                }
                items.forEach((item) => {
                    const tr = document.createElement('tr');
                    const idField = campo === 'servico' ? 'aten_rel_serv_id' : 'aten_rel_peca_id';
                    const descField = campo === 'servico' ? 'aten_rel_serv_descricao' : 'aten_rel_peca_descricao';
                    tr.innerHTML = `<td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm ${removerClasse}" data-id="${item[idField]}">
                            <i class="bi bi-trash" aria-hidden="true"></i>
                        </button>
                    </td><td></td>`;
                    tr.querySelector('td:last-child').textContent = item[descField];
                    tbody.appendChild(tr);
                });
            }

            function carregarServicosRelatorio() {
                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/servicos`)
                    .then((r) => renderizarItensTabela(r.data, '#tableServicos tbody', 'servico', 'btnRemoveServico', 'serviço'));
            }

            document.getElementById('btnAddServico')?.addEventListener('click', function () {
                const input = document.getElementById('servico_descricao');
                const descricao = input.value.trim();
                if (!descricao) {
                    mostrarFeedbackRelatorio('error', 'Informe a descrição do serviço.');
                    return;
                }
                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/servicos`, {
                    method: 'POST',
                    body: new URLSearchParams({ descricao }),
                })
                    .then((r) => {
                        input.value = '';
                        input.focus();
                        carregarServicosRelatorio();
                        mostrarFeedbackRelatorio('success', r.message);
                    })
                    .catch((err) => mostrarErroAjax({ status: err.status }, err.payload));
            });

            document.querySelector('#tableServicos')?.addEventListener('click', function (event) {
                const btn = event.target.closest('.btnRemoveServico');
                if (!btn) return;
                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/servicos/${btn.dataset.id}`, { method: 'DELETE' })
                    .then((r) => { carregarServicosRelatorio(); mostrarFeedbackRelatorio('success', r.message); })
                    .catch((err) => mostrarErroAjax({ status: err.status }, err.payload));
            });

            // ─── Peças (BF09 - checklist de trocada) ────────────────────────────
            function renderizarPecasRelatorio(items) {
                const tbody = document.querySelector('#tablePecas tbody');
                tbody.innerHTML = '';
                if (!items || !items.length) {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Nenhuma peça cadastrada.</td></tr>';
                    return;
                }
                items.forEach((item) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm btnRemovePeca" data-id="${item.aten_rel_peca_id}">
                            <i class="bi bi-trash" aria-hidden="true"></i>
                        </button>
                    </td><td></td><td class="text-center">${item.aten_rel_peca_trocada ? '<span class="badge bg-success">Trocada</span>' : '<span class="badge bg-secondary">Não trocada</span>'}</td>`;
                    tr.querySelector('td:nth-child(2)').textContent = item.aten_rel_peca_descricao;
                    tbody.appendChild(tr);
                });
            }

            function carregarPecasRelatorio() {
                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/pecas`).then((r) => renderizarPecasRelatorio(r.data));
            }

            document.getElementById('btnAddPeca')?.addEventListener('click', function () {
                const input = document.getElementById('peca_descricao');
                const trocada = document.getElementById('peca_trocada');
                const descricao = input.value.trim();
                if (!descricao) {
                    mostrarFeedbackRelatorio('error', 'Informe a descrição da peça.');
                    return;
                }
                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/pecas`, {
                    method: 'POST',
                    body: new URLSearchParams({ descricao, trocada: trocada.checked ? '1' : '0' }),
                })
                    .then((r) => {
                        input.value = '';
                        trocada.checked = false;
                        input.focus();
                        carregarPecasRelatorio();
                        mostrarFeedbackRelatorio('success', r.message);
                    })
                    .catch((err) => mostrarErroAjax({ status: err.status }, err.payload));
            });

            document.querySelector('#tablePecas')?.addEventListener('click', function (event) {
                const btn = event.target.closest('.btnRemovePeca');
                if (!btn) return;
                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/pecas/${btn.dataset.id}`, { method: 'DELETE' })
                    .then((r) => { carregarPecasRelatorio(); mostrarFeedbackRelatorio('success', r.message); })
                    .catch((err) => mostrarErroAjax({ status: err.status }, err.payload));
            });

            // ─── Ocorrências ────────────────────────────────────────────────────
            function renderizarOcorrenciasRelatorio(lista) {
                const tbody = document.querySelector('#tableOcorrencias tbody');
                tbody.innerHTML = '';
                if (!Array.isArray(lista) || !lista.length) return;
                lista.forEach((d) => {
                    const tr = document.createElement('tr');
                    tr.dataset.ocorrenciaId = d.ocorrencia_id;
                    tr.innerHTML = `<td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm btnRemoveOcorrencia">
                            <i class="bi bi-trash" aria-hidden="true"></i>
                        </button>
                    </td><td></td><td></td>`;
                    tr.children[1].textContent = d.ocorrencia;
                    tr.children[2].textContent = d.observacao || '';
                    tbody.appendChild(tr);
                });
            }

            function carregarOcorrenciasRelatorio() {
                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/ocorrencias`).then((r) => renderizarOcorrenciasRelatorio(r.data));
            }

            document.getElementById('btnAddOcorrencia')?.addEventListener('click', function () {
                const select = document.getElementById('ocorrencia_id');
                const observacao = document.getElementById('ocorrencia_observacao');
                if (!select.value) {
                    mostrarFeedbackRelatorio('error', 'Selecione uma ocorrência.');
                    return;
                }
                if (document.querySelector(`#tableOcorrencias tbody tr[data-ocorrencia-id="${select.value}"]`)) {
                    mostrarFeedbackRelatorio('error', 'Essa ocorrência já foi adicionada.');
                    return;
                }
                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/ocorrencias`, {
                    method: 'POST',
                    body: new URLSearchParams({ ocorrencia_id: select.value, observacao: observacao.value }),
                })
                    .then((r) => {
                        carregarOcorrenciasRelatorio();
                        mostrarFeedbackRelatorio('success', r.message);
                        select.value = '';
                        observacao.value = '';
                    })
                    .catch((err) => mostrarErroAjax({ status: err.status }, err.payload));
            });

            document.querySelector('#tableOcorrencias')?.addEventListener('click', function (event) {
                const btn = event.target.closest('.btnRemoveOcorrencia');
                if (!btn) return;
                const tr = btn.closest('tr');
                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/ocorrencias/${tr.dataset.ocorrenciaId}`, { method: 'DELETE' })
                    .then((r) => { tr.remove(); mostrarFeedbackRelatorio('success', r.message); })
                    .catch((err) => mostrarErroAjax({ status: err.status }, err.payload));
            });

            // ─── Descrição (itens: texto + foto opcional) ──────────────────────
            function renderizarDescricaoItens(items, legado) {
                const temLegado = !!(legado && legado.trim().length);
                document.getElementById('descricaoFormNovo').hidden = temLegado;
                const legadoBox = document.getElementById('descricaoLegadoBox');
                legadoBox.hidden = !temLegado;
                legadoBox.textContent = legado || '';
                if (temLegado) {
                    document.getElementById('listaDescricaoItens').innerHTML = '';
                    document.getElementById('descricaoItensVazio').hidden = true;
                    return;
                }

                const container = document.getElementById('listaDescricaoItens');
                container.innerHTML = '';
                document.getElementById('descricaoItensVazio').hidden = !!(items && items.length);

                (items || []).forEach((item) => {
                    const col = document.createElement('div');
                    col.className = 'col-md-4 mb-3';
                    const fotoHtml = item.foto_url
                        ? `<a href="#" class="anexo-thumb" data-type="image" data-src="${item.foto_url}">
                            <img src="${item.foto_url}" class="card-img-top" style="max-height:220px;object-fit:cover;" alt="Foto do item">
                        </a>`
                        : '';
                    col.innerHTML = `<div class="card h-100">
                        ${fotoHtml}
                        <div class="card-body">
                            <p class="card-text" style="white-space:pre-wrap;"></p>
                        </div>
                        <div class="card-footer text-end">
                            <button type="button" class="btn btn-outline-danger btn-sm btnRemoveDescricaoItem" data-id="${item.id}">
                                <i class="bi bi-trash" aria-hidden="true"></i> Excluir
                            </button>
                        </div>
                    </div>`;
                    col.querySelector('.card-text').textContent = item.texto;
                    container.appendChild(col);
                });
            }

            function carregarDescricaoItensRelatorio() {
                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/descricao-itens`).then((r) => renderizarDescricaoItens(r.data, r.legado));
            }

            document.getElementById('btnAddDescricaoItem')?.addEventListener('click', function () {
                const texto = document.getElementById('descricao_item_texto');
                if (!texto.value.trim()) {
                    mostrarFeedbackRelatorio('error', 'Descreva o item antes de adicionar.');
                    return;
                }
                const fotoInput = document.getElementById('descricao_item_foto');
                const fd = new FormData();
                fd.append('texto', texto.value.trim());
                if (fotoInput.files.length) fd.append('foto', fotoInput.files[0]);

                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/descricao-itens`, { method: 'POST', body: fd })
                    .then((r) => {
                        texto.value = '';
                        fotoInput.value = '';
                        const label = fotoInput.closest('.file-upload-group')?.querySelector('.file-upload-text');
                        if (label) label.textContent = 'Nenhuma foto selecionada';
                        carregarDescricaoItensRelatorio();
                        mostrarFeedbackRelatorio('success', r.message);
                    })
                    .catch((err) => mostrarErroAjax({ status: err.status }, err.payload));
            });

            document.getElementById('listaDescricaoItens')?.addEventListener('click', function (event) {
                const btn = event.target.closest('.btnRemoveDescricaoItem');
                if (!btn) return;
                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/descricao-itens/${btn.dataset.id}`, { method: 'DELETE' })
                    .then((r) => { carregarDescricaoItensRelatorio(); mostrarFeedbackRelatorio('success', r.message); })
                    .catch((err) => mostrarErroAjax({ status: err.status }, err.payload));
            });

            // ─── Perguntas do modelo (NC02/NC03) ────────────────────────────────
            // Pedido do cliente (2026-09-11): pergunta "repetivel" (ex.:
            // "Descricao do servico" com foto) pode ser respondida varias
            // vezes no mesmo relatorio - cada resposta e uma linha
            // independente, removivel individualmente, igual a antiga aba
            // Descricao (RF001), so que agora por pergunta em vez de fixa.
            function fotosRespostaHtml(fotos) {
                return (fotos || []).map((f) => `
                    <div class="d-inline-block m-1 position-relative">
                        <a href="#" class="anexo-thumb" data-type="image" data-src="${f.url}">
                            <img src="${f.url}" style="width:100px;height:75px;object-fit:cover;border-radius:.35rem;" alt="foto da resposta">
                        </a>
                        <button type="button" class="btn btn-sm btn-danger btnRemovePerguntaFoto position-absolute" style="top:2px;right:2px;" data-foto-id="${f.id}" aria-label="Excluir foto">
                            <i class="bi bi-trash" aria-hidden="true"></i>
                        </button>
                    </div>`).join('');
            }

            function campoRespostaHtml(p, valorAtual) {
                if (p.tipo === 2) {
                    return `<textarea class="form-control sbadmin-form-control resposta-valor" rows="3" placeholder="Resposta...">${valorAtual ? escapeHtml(valorAtual) : ''}</textarea>`;
                }
                const selecionadas = (valorAtual || '').split(',');
                return (p.opcoes || []).map((op) => {
                    const tipoInput = p.tipo === 0 ? 'checkbox' : 'radio';
                    const marcado = selecionadas.includes(String(op.id)) ? 'checked' : '';
                    return `<div class="form-check">
                        <input type="${tipoInput}" class="form-check-input resposta-opcao" name="opcao_${p.id}" value="${op.id}" ${marcado}>
                        <label class="form-check-label">${escapeHtml(op.texto)}</label>
                    </div>`;
                }).join('');
            }

            function renderizarPerguntasRelatorio(perguntas) {
                const container = document.getElementById('listaPerguntasRelatorio');
                container.innerHTML = '';
                if (!perguntas || !perguntas.length) {
                    container.innerHTML = '<p class="text-body-secondary mb-0">Este modelo não tem perguntas cadastradas.</p>';
                    return;
                }

                perguntas.forEach((p) => {
                    const card = document.createElement('div');
                    card.className = 'sbadmin-card mb-3';
                    card.dataset.perguntaId = p.id;

                    if (p.repetivel) {
                        const linhas = (p.respostas || []).map((r) => `
                            <div class="d-flex align-items-start gap-2 border rounded p-2 mb-2" data-resposta-id="${r.id}">
                                <div class="flex-grow-1">
                                    <p class="mb-1" style="white-space:pre-wrap;">${escapeHtml(r.valor || '')}</p>
                                    <div class="fotos-resposta">${fotosRespostaHtml(r.fotos)}</div>
                                </div>
                                <button type="button" class="btn btn-outline-danger btn-sm btnRemoverResposta" data-resposta-id="${r.id}" aria-label="Remover resposta">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>
                            </div>`).join('');

                        card.innerHTML = `<div class="sbadmin-card-body">
                            <div class="d-flex justify-content-between align-items-baseline mb-2">
                                <p class="fw-bold mb-0">${escapeHtml(p.texto)}</p>
                                <span class="badge bg-info">múltiplas respostas</span>
                            </div>
                            <div class="respostas-repetivel mb-2">
                                ${linhas || '<p class="text-body-secondary small mb-2">Nenhuma resposta adicionada ainda.</p>'}
                            </div>
                            <div class="border-top pt-2">
                                <div class="mb-2 campo-resposta">${campoRespostaHtml(p, '')}</div>
                                ${p.permite_anexo ? `<div class="mb-2">
                                    <label class="sbadmin-form-label">Foto (opcional)</label>
                                    <input type="file" class="form-control sbadmin-form-control resposta-foto" accept="image/*">
                                </div>` : ''}
                                <button type="button" class="btn btn-outline-primary btn-sm btnSalvarResposta">
                                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Adicionar outra resposta
                                </button>
                            </div>
                        </div>`;
                        container.appendChild(card);
                        return;
                    }

                    card.innerHTML = `<div class="sbadmin-card-body">
                        <p class="fw-bold mb-2">${escapeHtml(p.texto)}</p>
                        <div class="mb-2 campo-resposta">${campoRespostaHtml(p, p.valor)}</div>
                        ${p.permite_anexo ? `<div class="mb-2">
                            <label class="sbadmin-form-label">Foto (opcional)</label>
                            <input type="file" class="form-control sbadmin-form-control resposta-foto" accept="image/*">
                            <div class="fotos-resposta mt-2">${fotosRespostaHtml(p.fotos)}</div>
                        </div>` : ''}
                        <button type="button" class="btn btn-outline-success btn-sm btnSalvarResposta">
                            <i class="bi bi-check-lg" aria-hidden="true"></i> Salvar resposta
                        </button>
                    </div>`;
                    container.appendChild(card);
                });
            }

            function carregarPerguntasRelatorio() {
                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/respostas`).then((r) => renderizarPerguntasRelatorio(r.data));
            }

            document.getElementById('listaPerguntasRelatorio')?.addEventListener('click', function (event) {
                const btnFoto = event.target.closest('.btnRemovePerguntaFoto');
                if (btnFoto) {
                    fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/respostas-fotos/${btnFoto.dataset.fotoId}`, { method: 'DELETE' })
                        .then((r) => { carregarPerguntasRelatorio(); mostrarFeedbackRelatorio('success', r.message); })
                        .catch((err) => mostrarErroAjax({ status: err.status }, err.payload));
                    return;
                }

                const btnRemoverResposta = event.target.closest('.btnRemoverResposta');
                if (btnRemoverResposta) {
                    fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/respostas/${btnRemoverResposta.dataset.respostaId}`, { method: 'DELETE' })
                        .then((r) => { carregarPerguntasRelatorio(); mostrarFeedbackRelatorio('success', r.message); })
                        .catch((err) => mostrarErroAjax({ status: err.status }, err.payload));
                    return;
                }

                const btnSalvar = event.target.closest('.btnSalvarResposta');
                if (!btnSalvar) return;
                const card = btnSalvar.closest('[data-pergunta-id]');
                const perguntaId = card.dataset.perguntaId;

                let valor = '';
                const textarea = card.querySelector('.resposta-valor');
                if (textarea) {
                    valor = textarea.value;
                } else {
                    valor = Array.from(card.querySelectorAll('.resposta-opcao:checked')).map((el) => el.value).join(',');
                }

                const fd = new FormData();
                fd.append('pergunta_id', perguntaId);
                fd.append('valor', valor);
                const fotoInput = card.querySelector('.resposta-foto');
                if (fotoInput && fotoInput.files.length) fd.append('foto', fotoInput.files[0]);

                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/respostas`, { method: 'POST', body: fd })
                    .then((r) => {
                        carregarPerguntasRelatorio();
                        mostrarFeedbackRelatorio('success', r.message);
                    })
                    .catch((err) => mostrarErroAjax({ status: err.status }, err.payload));
            });

            // ─── Compartilhamento (BF07) ────────────────────────────────────────
            function renderizarCompartilhamentosRelatorio(items) {
                const tbody = document.querySelector('#tableCompartilhamentos tbody');
                tbody.innerHTML = '';
                if (!items || !items.length) {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Nenhum compartilhamento registrado.</td></tr>';
                    return;
                }
                items.forEach((item) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td></td><td></td><td class="text-monospace small"></td>`;
                    tr.children[0].textContent = item.criado_em;
                    tr.children[1].textContent = item.canal || '-';
                    tr.children[2].textContent = item.hash;
                    tbody.appendChild(tr);
                });
            }

            function carregarCompartilhamentosRelatorio() {
                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/compartilhamentos`).then((r) => renderizarCompartilhamentosRelatorio(r.data));
            }

            document.getElementById('btnGerarComprovante')?.addEventListener('click', function () {
                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/compartilhamentos`, {
                    method: 'POST',
                    body: new URLSearchParams({ canal: 'painel-web' }),
                })
                    .then((r) => {
                        carregarCompartilhamentosRelatorio();
                        mostrarFeedbackRelatorio('success', r.message);
                    })
                    .catch((err) => mostrarErroAjax({ status: err.status }, err.payload));
            });
            // ─── Anexos (só fotos — pedido do cliente, 2026-09-14) ─────────────────
            function renderizarAnexosRelatorio(data) {
                // Arquivos/vídeos legados (enviados antes desta tela virar
                // "só fotos") continuam listados, só sem opção de novo upload.
                const arquivosList = document.getElementById('anexosArquivosList');
                if (arquivosList) {
                    arquivosList.innerHTML = '';
                    if (data.arquivos && data.arquivos.length) {
                        const ul = document.createElement('ul');
                        ul.className = 'list-unstyled';
                        data.arquivos.forEach((item) => {
                            const li = document.createElement('li');
                            li.className = 'd-flex align-items-center justify-content-between mb-1';
                            li.innerHTML = `<a href="${item.url}" target="_blank"></a>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-anexo" data-type="arquivo" data-id="${item.id}" aria-label="Excluir anexo">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>`;
                            li.querySelector('a').textContent = item.name;
                            ul.appendChild(li);
                        });
                        arquivosList.appendChild(ul);
                    }
                }

                const fotosContainer = document.getElementById('anexosFotosContainer');
                fotosContainer.innerHTML = '';
                (data.fotos || []).forEach((item) => {
                    const div = document.createElement('div');
                    div.className = 'm-1 position-relative';
                    div.style.width = '120px';
                    div.innerHTML = `<a href="#" class="anexo-thumb" data-type="image" data-src="${item.url}">
                        <img src="${item.thumb_url}" style="width:120px;height:80px;object-fit:cover;border-radius:.35rem;" alt="foto">
                    </a>
                    <button type="button" class="btn btn-sm btn-danger btn-delete-anexo position-absolute" style="top:4px;right:4px;" data-type="foto" data-id="${item.id}" aria-label="Excluir foto">
                        <i class="bi bi-trash" aria-hidden="true"></i>
                    </button>`;
                    fotosContainer.appendChild(div);
                });

                const videosContainer = document.getElementById('anexosVideosContainer');
                if (videosContainer) {
                    videosContainer.innerHTML = '';
                    (data.videos || []).forEach((item) => {
                        const div = document.createElement('div');
                        div.className = 'm-1 position-relative';
                        div.style.width = '160px';
                        div.innerHTML = `<a href="#" class="anexo-thumb" data-type="video" data-src="${item.url}">
                            <img src="${item.thumb_url}" style="width:160px;height:90px;object-fit:cover;border-radius:.35rem;" alt="video">
                        </a>
                        <button type="button" class="btn btn-sm btn-danger btn-delete-anexo position-absolute" style="top:4px;right:4px;" data-type="video" data-id="${item.id}" aria-label="Excluir vídeo">
                            <i class="bi bi-trash" aria-hidden="true"></i>
                        </button>`;
                        videosContainer.appendChild(div);
                    });
                }
            }

            function refreshAnexosRelatorio() {
                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/anexos`).then(renderizarAnexosRelatorio);
            }

            document.addEventListener('click', function (event) {
                const btn = event.target.closest('.btn-delete-anexo');
                if (!btn) return;
                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/anexos/${btn.dataset.type}/${btn.dataset.id}`, { method: 'DELETE' })
                    .then((r) => { refreshAnexosRelatorio(); mostrarFeedbackRelatorio('success', r.message); })
                    .catch((err) => mostrarErroAjax({ status: err.status }, err.payload));
            });

            document.getElementById('uploadFotosInput')?.addEventListener('change', function () {
                const nomes = Array.from(this.files).map((f) => f.name).join(', ');
                document.getElementById('uploadFotosNome').textContent = nomes || 'Selecione fotos para upload';
            });

            function enviarAnexosRelatorio() {
                const fotos = document.getElementById('uploadFotosInput');
                if (!fotos || !fotos.files.length) return;

                const fd = new FormData();
                Array.from(fotos.files).forEach((f) => fd.append('fotos[]', f));

                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/upload-anexos`, { method: 'POST', body: fd })
                    .then((r) => {
                        mostrarFeedbackRelatorio('success', r.message || 'Uploads concluídos.');
                        fotos.value = '';
                        document.getElementById('uploadFotosNome').textContent = 'Selecione fotos para upload';
                        refreshAnexosRelatorio();
                    })
                    .catch((err) => mostrarErroAjax({ status: err.status }, err.payload));
            }

            document.getElementById('btnEnviarAnexosRelatorio')?.addEventListener('click', enviarAnexosRelatorio);

            // ─── Assinaturas ────────────────────────────────────────────────────
            function setupSignatureCanvas(canvasId) {
                const canvas = document.getElementById(canvasId);
                if (!canvas) return null;

                const ctx = canvas.getContext('2d');
                ctx.lineWidth = 2.2;
                ctx.lineCap = 'round';
                ctx.strokeStyle = '#111';

                let drawing = false;
                let hasStroke = false;

                const getPoint = (event) => {
                    const rect = canvas.getBoundingClientRect();
                    const touch = event.touches ? event.touches[0] : event;
                    return { x: touch.clientX - rect.left, y: touch.clientY - rect.top };
                };

                const start = (event) => {
                    event.preventDefault();
                    drawing = true;
                    hasStroke = true;
                    const p = getPoint(event);
                    ctx.beginPath();
                    ctx.moveTo(p.x, p.y);
                };
                const move = (event) => {
                    if (!drawing) return;
                    event.preventDefault();
                    const p = getPoint(event);
                    ctx.lineTo(p.x, p.y);
                    ctx.stroke();
                };
                const end = (event) => {
                    if (!drawing) return;
                    event.preventDefault();
                    drawing = false;
                };
                const clear = () => {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    canvas.style.background = '#fff';
                    hasStroke = false;
                };

                canvas.addEventListener('mousedown', start);
                canvas.addEventListener('mousemove', move);
                canvas.addEventListener('mouseup', end);
                canvas.addEventListener('mouseleave', end);
                canvas.addEventListener('touchstart', start);
                canvas.addEventListener('touchmove', move);
                canvas.addEventListener('touchend', end);
                canvas.addEventListener('touchcancel', end);

                clear();

                return { clear, hasStroke: () => hasStroke, getDataUrl: () => canvas.toDataURL('image/png') };
            }

            const assinaturaResponsavelCanvas = setupSignatureCanvas('assinaturaResponsavelCanvas');
            const assinaturaClienteCanvas = setupSignatureCanvas('assinaturaClienteCanvas');
            window.__assinaturaData = {};

            document.querySelectorAll('.btn-clear-signature').forEach((btn) => {
                btn.addEventListener('click', function () {
                    const tipo = this.dataset.signature;
                    if (tipo === 'responsavel') assinaturaResponsavelCanvas?.clear();
                    if (tipo === 'cliente') assinaturaClienteCanvas?.clear();
                    delete window.__assinaturaData[tipo];
                });
            });

            function salvarAssinatura(tipo, dataUrl) {
                const status = document.querySelector('#form_relatorio_assinaturas input[name="aten_rel_status"]:checked')?.value;
                const observacaoSupervisor = document.getElementById('aten_rel_observacao_supervisor')?.value ?? '';
                const body = { aten_rel_status: status, observacao_supervisor: observacaoSupervisor };
                body[`assinatura_${tipo}`] = dataUrl;
                if (tipo === 'cliente') {
                    body.assinatura_cliente_nome = document.getElementById('assinatura_cliente_nome').value.trim();
                    body.assinatura_cliente_cpf = document.getElementById('assinatura_cliente_cpf').value.trim();
                }

                return fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/assinaturas`, {
                    method: 'POST',
                    body: new URLSearchParams(body),
                });
            }

            document.querySelectorAll('.btn-save-signature').forEach((btn) => {
                btn.addEventListener('click', function () {
                    const tipo = this.dataset.signature;
                    const canvasObj = tipo === 'responsavel' ? assinaturaResponsavelCanvas : assinaturaClienteCanvas;

                    if (!canvasObj || !canvasObj.hasStroke()) {
                        mostrarFeedbackRelatorio('error', 'Desenhe a assinatura antes de salvar.');
                        return;
                    }

                    if (tipo === 'cliente') {
                        if (!document.getElementById('assinatura_cliente_nome').value.trim()) {
                            mostrarFeedbackRelatorio('error', 'Informe o nome de quem está assinando.');
                            return;
                        }
                        if (!document.getElementById('assinatura_cliente_cpf').value.trim()) {
                            mostrarFeedbackRelatorio('error', 'Informe o CPF de quem está assinando.');
                            return;
                        }
                    }

                    const dataUrl = canvasObj.getDataUrl();
                    salvarAssinatura(tipo, dataUrl)
                        .then((response) => {
                            mostrarFeedbackRelatorio('success', 'Assinatura salva com sucesso.');
                            canvasObj.clear();
                            const assin = response.data?.assinaturas?.[tipo];
                            if (assin) {
                                const previewId = tipo === 'responsavel' ? 'assinaturaResponsavelPreview' : 'assinaturaClientePreview';
                                document.getElementById(previewId).innerHTML = `<img src="${assin}" class="img-fluid border" alt="Assinatura ${tipo}">`;
                            }
                        })
                        .catch((err) => mostrarErroAjax({ status: err.status }, err.payload));
                });
            });

            function carregarAssinaturasRelatorio() {
                fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/assinaturas`).then((data) => {
                    if (data.assinaturas?.responsavel) {
                        document.getElementById('assinaturaResponsavelPreview').innerHTML = `<img src="${data.assinaturas.responsavel}" class="img-fluid border" alt="Assinatura Responsável">`;
                    }
                    if (data.assinaturas?.cliente) {
                        document.getElementById('assinaturaClientePreview').innerHTML = `<img src="${data.assinaturas.cliente}" class="img-fluid border" alt="Assinatura Cliente">`;
                    }
                });
            }

            // ─── Dispatcher de aba (chamado ao clicar numa aba, ver show.blade.php) ──
            function carregarAbaRelatorio(aba) {
                switch (aba) {
                    case 'dados': carregarDadosRelatorio(); break;
                    case 'horarios': carregarHorariosRelatorio(); break;
                    case 'clima': carregarClimaRelatorio(); break;
                    case 'ocorrencias': carregarOcorrenciasRelatorio(); break;
                    case 'servicos': carregarServicosRelatorio(); break;
                    case 'pecas': carregarPecasRelatorio(); break;
                    case 'descricao': carregarDescricaoItensRelatorio(); break;
                    case 'perguntas': carregarPerguntasRelatorio(); break;
                    case 'anexos': refreshAnexosRelatorio(); break;
                    case 'compartilhamento': carregarCompartilhamentosRelatorio(); break;
                    case 'assinatura': carregarAssinaturasRelatorio(); break;
                }
            }

            // ─── Botão único "Atualizar" — despacha pra aba ativa ──────────────
            document.getElementById('btnAtualizarRelatorio')?.addEventListener('click', function () {
                const abaAtiva = Alpine.$data(document.getElementById('relatorio-root')).tab;

                const semFormulario = ['servicos', 'pecas', 'descricao', 'ocorrencias', 'perguntas', 'compartilhamento', 'anexos'];
                if (semFormulario.includes(abaAtiva)) {
                    mostrarFeedbackRelatorio('error', 'Use os botões para adicionar e remover itens nesta aba.');
                    return;
                }

                if (abaAtiva === 'info-adicionais') {
                    document.getElementById('form_informacoes_adicionais').requestSubmit();
                    return;
                }

                if (abaAtiva === 'observacao-interna') {
                    document.getElementById('form_observacao_interna').requestSubmit();
                    return;
                }

                if (abaAtiva === 'assinatura') {
                    const status = document.querySelector('#form_relatorio_assinaturas input[name="aten_rel_status"]:checked')?.value;
                    const observacaoSupervisor = document.getElementById('aten_rel_observacao_supervisor')?.value ?? '';
                    fetchJson(`${RELATORIOS_BASE_URL}/${RELATORIO_ID}/assinaturas`, {
                        method: 'POST',
                        body: new URLSearchParams({ aten_rel_status: status, observacao_supervisor: observacaoSupervisor }),
                    })
                        .then((r) => {
                            mostrarFeedbackRelatorio('success', r.message || 'Status atualizado.');
                            if (String(status) === '2') { window.location.reload(); }
                        })
                        .catch((err) => mostrarErroAjax({ status: err.status }, err.payload));
                    return;
                }

                const formPorAba = {
                    dados: 'form_relatorio_dados',
                    horarios: 'form_relatorio_horarios',
                    clima: 'form_relatorio_clima',
                };
                const formId = formPorAba[abaAtiva];
                if (!formId) {
                    mostrarFeedbackRelatorio('error', 'Nenhuma ação definida para esta aba.');
                    return;
                }

                const form = document.getElementById(formId);
                const body = new URLSearchParams(new FormData(form));

                fetchJson(form.dataset.action, { method: 'POST', body })
                    .then((r) => {
                        mostrarFeedbackRelatorio('success', r.message);
                        carregarAbaRelatorio(abaAtiva);
                    })
                    .catch((err) => mostrarErroAjax({ status: err.status }, err.payload));
            });

            document.addEventListener('DOMContentLoaded', function () {
                window.setupAutocomplete?.('#rel_aten_label', '#rel_aten_id', '{{ route('atendimentos_relatorios.autocomplete') }}', { minLength: 2 });
            });
        </script>
    @endpush
</x-layout>
