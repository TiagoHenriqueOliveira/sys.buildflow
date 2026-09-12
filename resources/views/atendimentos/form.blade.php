{{-- Atendimentos — página dedicada de cadastro/edição (substitui o modal:
     4 abas + muitos campos ficavam apertados demais num modal). Mantém a
     mesma arquitetura de dados de antes (não é so template): Dados +
     Observações enviados via fetch()/JSON pro mesmo endpoint de sempre
     (store/update), e ao criar um atendimento novo o backend devolve o
     aten_id na hora, liberando as abas de Observações/Equipamentos/Anexos
     sem sair da página nem recarregar — perder isso seria regressão de
     funcionalidade. Único <x-data> raiz (id="atendimento-form-root") como
     em clientes/form.blade.php — nunca usar document.querySelector('[x-data]')
     aqui, o <html> do layout sbadmin também tem x-data próprio (ver
     memória do projeto sobre o bug de geolocalização). --}}
@php
    $mascararTelefone = fn (?string $v) => match (strlen((string) $v)) {
        11 => preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $v),
        10 => preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $v),
        default => $v,
    };

    $editando = $atendimento->exists;
@endphp
<x-layout :title="$editando ? 'Atendimentos | Editar' : 'Atendimentos | Novo'">
    <div
        id="atendimento-form-root"
        x-data="{
            tab: 'dados',
            editando: {{ $editando ? 'true' : 'false' }},
            entregaTecnica: {{ old('aten_entrega_tecnica', $atendimento->aten_entrega_tecnica) ? 'true' : 'false' }},
        }"
    >
        <div class="sbadmin-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="sbadmin-page-heading" x-text="editando ? 'Editar Atendimento' : 'Novo Atendimento'">{{ $editando ? 'Editar Atendimento' : 'Novo Atendimento' }}</h2>
                <p class="sbadmin-page-subheading">Cadastre os dados do atendimento técnico.</p>
            </div>
        </div>

        <div id="atendimento-feedback" class="mb-2"></div>

        <div class="sbadmin-card">
            <div class="sbadmin-card-body">
                <ul class="nav nav-tabs mb-3 flex-nowrap overflow-x-auto overflow-y-hidden" role="tablist">
                    <li class="nav-item text-nowrap">
                        <button type="button" class="nav-link" :class="{ active: tab === 'dados' }" @click="tab = 'dados'">Dados</button>
                    </li>
                    <li class="nav-item text-nowrap">
                        <button
                            type="button"
                            id="tab-observacoes-tab"
                            data-label="Observações"
                            class="nav-link aten-tab-restrita"
                            :class="{ active: tab === 'observacoes', disabled: !editando }"
                            :disabled="!editando"
                            @click="editando && (tab = 'observacoes')"
                        >Observações</button>
                    </li>
                    <li class="nav-item text-nowrap">
                        <button
                            type="button"
                            id="tab-equipamentos-tab"
                            data-label="Equipamentos"
                            class="nav-link aten-tab-restrita"
                            :class="{ active: tab === 'equipamentos', disabled: !editando }"
                            :disabled="!editando"
                            @click="editando && (tab = 'equipamentos')"
                        >Equipamentos</button>
                    </li>
                    <li class="nav-item text-nowrap">
                        <button
                            type="button"
                            id="tab-anexos-aten-tab"
                            data-label="Anexos"
                            class="nav-link aten-tab-restrita"
                            :class="{ active: tab === 'anexos', disabled: !editando }"
                            :disabled="!editando"
                            @click="editando && (tab = 'anexos')"
                        >Anexos</button>
                    </li>
                </ul>

                {{-- Formulário envolve Dados + Observações para compartilhar o mesmo submit (igual ao original) --}}
                <form id="form_atendimento" method="POST" action="{{ $editando ? route('atendimentos.update', $atendimento->aten_id) : route('atendimentos.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="aten_method" value="{{ $editando ? 'PUT' : 'POST' }}">
                    <input type="hidden" name="aten_id" id="aten_id" value="{{ $atendimento->aten_id }}">

                    <div x-show="tab === 'dados'">
                        <div class="row">
                            <div class="col-6 col-md-3">
                                <x-sbadmin::form.select
                                    id="aten_natureza_id"
                                    name="aten_natureza_id"
                                    label="Natureza"
                                    :options="$naturezasAtendimentos->pluck('nat_aten_descricao', 'nat_aten_id')->all()"
                                    :value="old('aten_natureza_id', $atendimento->aten_natureza_id)"
                                    placeholder="Selecione..."
                                    required
                                />
                            </div>
                            <div class="col-6 col-md-3">
                                <x-sbadmin::form.select
                                    id="aten_usuario_id"
                                    name="aten_usuario_id"
                                    label="Técnico"
                                    :options="$usuarios->pluck('user_nome', 'user_id')->all()"
                                    :value="old('aten_usuario_id', $atendimento->aten_usuario_id)"
                                    placeholder="Selecione..."
                                    required
                                />
                            </div>
                            <div class="col-6 col-md-3">
                                <x-sbadmin::form.input id="aten_dt_inicio" type="date" name="aten_dt_inicio" label="Início" :value="old('aten_dt_inicio', optional($atendimento->aten_dt_inicio)->format('Y-m-d'))" required />
                            </div>
                            <div class="col-6 col-md-3">
                                <x-sbadmin::form.input id="aten_dt_fim" type="date" name="aten_dt_fim" label="Fim" :value="old('aten_dt_fim', optional($atendimento->aten_dt_fim)->format('Y-m-d'))" required />
                            </div>
                        </div>

                        <div class="row align-items-end">
                            <div class="col-md-8">
                                <div class="sbadmin-form-group">
                                    <label for="aten_cliente_nome" class="sbadmin-form-label">Cliente<span class="sbadmin-required" aria-hidden="true">*</span></label>
                                    <input type="hidden" id="aten_cliente_id" name="aten_cliente_id" value="{{ old('aten_cliente_id', $atendimento->aten_cliente_id) }}">
                                    <input
                                        type="text"
                                        class="form-control sbadmin-form-control"
                                        id="aten_cliente_nome"
                                        placeholder="Digite o nome do cliente"
                                        value="{{ old('aten_cliente_nome', optional($atendimento->cliente)->cli_nome) }}"
                                        autocomplete="off"
                                        required
                                    >
                                </div>
                            </div>
                            <div class="col-md-3">
                                <x-sbadmin::form.input
                                    id="aten_telefone"
                                    name="aten_telefone"
                                    label="Telefone"
                                    :value="old('aten_telefone', $mascararTelefone($atendimento->aten_telefone))"
                                    maxlength="20"
                                    placeholder="(00) 00000-0000"
                                    oninput="this.value = window.formatarTelefone(this.value)"
                                />
                            </div>
                            <div class="col-md-1 mb-3">
                                <a id="btnWhatsapp" href="#" target="_blank" class="btn btn-success btn-sm w-100" title="Abrir WhatsApp">
                                    <i class="bi bi-whatsapp" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>

                        {{-- BF03 — dados do cliente vinculado, sem navegação adicional. --}}
                        <div id="cliente-resumo-box" class="sbadmin-card mb-3" hidden>
                            <div class="sbadmin-card-body py-2 small">
                                <div><strong>Segmento:</strong> <span id="cliente-resumo-segmento">—</span></div>
                                <div><strong>Classificação:</strong> <span id="cliente-resumo-classificacao">—</span></div>
                                <div><strong>Contato principal:</strong> <span id="cliente-resumo-contato">—</span></div>
                                <div><strong>Cidade/UF:</strong> <span id="cliente-resumo-cidade">—</span></div>
                            </div>
                        </div>

                        <x-sbadmin::form.input id="aten_endereco" name="aten_endereco" label="Endereço" :value="old('aten_endereco', $atendimento->aten_endereco)" maxlength="100" />

                        <div class="row">
                            <div class="col-md-4">
                                <x-sbadmin::form.input id="aten_nr_proposta" name="aten_nr_proposta" label="Nº Proposta" :value="old('aten_nr_proposta', $atendimento->aten_nr_proposta)" maxlength="20" />
                            </div>
                            <div class="col-md-4">
                                <x-sbadmin::form.input id="aten_contato" name="aten_contato" label="Contato" :value="old('aten_contato', $atendimento->aten_contato)" maxlength="50" />
                            </div>
                            <div class="col-md-4">
                                <x-sbadmin::form.input id="aten_responsavel" name="aten_responsavel" label="Responsável" :value="old('aten_responsavel', $atendimento->aten_responsavel)" maxlength="50" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="sbadmin-form-group">
                                    <label class="sbadmin-form-label">Entrega Téc.</label>
                                    <div class="form-check form-switch">
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            role="switch"
                                            id="aten_entrega_tecnica"
                                            name="aten_entrega_tecnica"
                                            value="1"
                                            x-model="entregaTecnica"
                                        >
                                        <label class="form-check-label" for="aten_entrega_tecnica">
                                            <span class="sbadmin-badge sbadmin-badge-pill" :class="entregaTecnica ? 'sbadmin-badge-success' : 'sbadmin-badge-neutral'" x-text="entregaTecnica ? 'Sim' : 'Não'">Não</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="sbadmin-form-group">
                                    <label class="sbadmin-form-label">Status</label>
                                    <div class="pt-1">
                                        @foreach([0 => 'Não iniciada', 1 => 'Paralisada', 2 => 'Em andamento', 3 => 'Concluída'] as $val => $label)
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" id="aten_status_{{ $val }}" name="aten_status" value="{{ $val }}" @checked((int) old('aten_status', $atendimento->aten_status ?? 0) === $val)>
                                                <label class="form-check-label" for="aten_status_{{ $val }}">{{ $label }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-show="tab === 'observacoes'" x-cloak>
                        <x-sbadmin::form.textarea id="aten_obs_tecnica" name="aten_obs_tecnica" label="Técnicas" rows="4" />
                        <x-sbadmin::form.textarea id="aten_obs_cliente" name="aten_obs_cliente" label="Cliente" rows="4" />
                        <x-sbadmin::form.textarea id="aten_obs_manutencao" name="aten_obs_manutencao" label="Manutenção" rows="4" />
                    </div>

                </form>

                {{-- Aba Equipamentos --}}
                <div x-show="tab === 'equipamentos'" x-cloak>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-6">
                            <label for="aten_equip_descricao" class="sbadmin-form-label">Descrição</label>
                            <input type="text" class="form-control sbadmin-form-control" id="aten_equip_descricao" maxlength="255" placeholder="Ex.: Ar condicionado">
                        </div>
                        <div class="col-auto">
                            <button type="button" id="btnAdicionarEquipamento" class="btn btn-success btn-sm">
                                <i class="bi bi-plus-lg" aria-hidden="true"></i> Adicionar
                            </button>
                        </div>
                    </div>

                    <hr>

                    <h6 class="fw-bold">Equipamentos Cadastrados</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover" id="table_equipamentos">
                            <thead>
                                <tr>
                                    <th class="text-center align-middle" style="width: 50px;">Ações</th>
                                    <th class="align-middle">Descrição</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                {{-- Aba Anexos --}}
                <div x-show="tab === 'anexos'" x-cloak>
                    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                        <label class="btn btn-primary btn-sm mb-0">
                            <i class="bi bi-upload" aria-hidden="true"></i>
                            <input type="file" class="d-none" id="aten_anexo_file" multiple>
                        </label>
                        <span class="text-body-secondary small" id="aten_anexo_nome">Selecione arquivos para upload</span>
                        <button type="button" class="btn btn-success btn-sm ms-auto" id="btnUploadAnexoAten">
                            <i class="bi bi-cloud-upload" aria-hidden="true"></i> Enviar
                        </button>
                    </div>
                    <div id="aten_anexos_lista"></div>
                </div>

                {{-- Rodapé único (Salvar + Voltar), sempre visível independente da
                     aba ativa — Equipamentos/Anexos salvam na hora via fetch() e
                     não precisam de um botão Salvar próprio, mas o usuário pediu
                     um único componente fixo em vez de um "Voltar" avulso por aba.
                     `form="form_atendimento"` associa o botão ao form sem precisar
                     ficar dentro dele (ele só teria os campos de Dados/Observações). --}}
                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                    <button type="submit" form="form_atendimento" class="btn btn-success">
                        <i class="bi bi-check-lg" aria-hidden="true"></i> Salvar
                    </button>
                    <a href="{{ route('atendimentos.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg" aria-hidden="true"></i> Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            function preencherFormularioAtendimento(atendimento) {
                document.getElementById('aten_id').value = atendimento.aten_id || '';
                document.getElementById('aten_natureza_id').value = atendimento.aten_natureza_id || '';
                document.getElementById('aten_cliente_id').value = atendimento.aten_cliente_id || '';
                document.getElementById('aten_cliente_nome').value = atendimento.aten_cliente_nome || '';
                carregarResumoCliente(atendimento.aten_cliente_id);
                document.getElementById('aten_usuario_id').value = atendimento.aten_usuario_id || '';
                document.getElementById('aten_nr_proposta').value = atendimento.aten_nr_proposta || '';
                document.getElementById('aten_contato').value = atendimento.aten_contato || '';
                document.getElementById('aten_responsavel').value = atendimento.aten_responsavel || '';
                const telefone = atendimento.aten_telefone || '';
                document.getElementById('aten_telefone').value = window.formatarTelefone(telefone);
                atualizarLinkWhatsapp(telefone);
                document.getElementById('aten_endereco').value = atendimento.aten_endereco || '';
                document.getElementById('aten_dt_inicio').value = atendimento.aten_dt_inicio || '';
                document.getElementById('aten_dt_fim').value = atendimento.aten_dt_fim || '';

                document.querySelectorAll('input[name="aten_status"]').forEach((el) => {
                    el.checked = el.value === String(atendimento.aten_status ?? '0');
                });

                document.getElementById('aten_method').value = 'PUT';
                document.getElementById('form_atendimento').action = '{{ url('/atendimentos') }}/' + atendimento.aten_id;
            }

            function habilitarAbas(habilitar) {
                document.querySelectorAll('.aten-tab-restrita').forEach((el) => {
                    el.disabled = !habilitar;
                    el.classList.toggle('disabled', !habilitar);
                });
            }

            function atualizarIconeAba(tabId, temDados) {
                const tab = document.getElementById(tabId);
                if (!tab) return;
                const base = tab.dataset.label || tab.textContent.replace(/\s*✔.*$/, '').trim();
                tab.dataset.label = base;
                tab.textContent = temDados ? base + ' ✔' : base;
            }

            function atualizarLinkWhatsapp(telefone) {
                const numero = (telefone || document.getElementById('aten_telefone').value || '').replace(/\D/g, '');
                const btn = document.getElementById('btnWhatsapp');
                btn.href = numero.length >= 10 ? 'https://wa.me/55' + numero : '#';
            }

            function csrfToken() {
                return document.querySelector('meta[name="csrf-token"]')?.content || '';
            }

            function mostrarFeedbackModal(tipo, mensagem) {
                const container = document.getElementById('atendimento-feedback');
                if (!container) return;
                const classe = tipo === 'success' ? 'sbadmin-alert-success' : 'sbadmin-alert-error';
                const icone = tipo === 'success' ? 'bi-check-circle-fill' : 'bi-x-circle-fill';
                container.innerHTML = `<div class="sbadmin-alert ${classe}" role="alert">
                    <i class="bi ${icone} sbadmin-alert-icon" aria-hidden="true"></i>
                    <div class="sbadmin-alert-content">${mensagem}</div>
                </div>`;
                window.setTimeout(() => { container.innerHTML = ''; }, 4000);
            }

            document.getElementById('aten_telefone')?.addEventListener('input', () => atualizarLinkWhatsapp());

            document.getElementById('form_atendimento')?.addEventListener('submit', function (event) {
                event.preventDefault();

                const form = event.target;
                const estadoFeedback = window.iniciarFeedbackSalvamento(form);

                fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                    body: new FormData(form),
                })
                    .then(async (response) => {
                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            throw payload;
                        }
                        return payload;
                    })
                    .then((response) => {
                        mostrarFeedbackModal('success', response.message || 'Salvo com sucesso.');
                        window.pararFeedbackSalvamento(estadoFeedback);

                        if (response.aten_id && response.atendimento) {
                            const root = document.getElementById('atendimento-form-root');
                            Alpine.$data(root).editando = true;
                            window.history.replaceState({}, '', '{{ url('/atendimentos') }}/' + response.aten_id + '/edit');
                            document.title = document.title.replace('Novo', 'Editar');
                            preencherFormularioAtendimento(response.atendimento);
                            habilitarAbas(true);
                            carregarObservacoes(response.aten_id);
                            carregarEquipamentos(response.aten_id);
                            carregarAnexosAtendimento(response.aten_id);
                        } else {
                            carregarObservacoes(document.getElementById('aten_id').value);
                        }
                    })
                    .catch((payload) => {
                        window.pararFeedbackSalvamento(estadoFeedback);
                        const mensagens = Object.values(payload?.errors || {}).flat();
                        mostrarFeedbackModal('error', mensagens.length ? mensagens.join('<br>') : (payload?.message || 'Erro inesperado ao salvar.'));
                    });
            });

            function carregarObservacoes(atenId) {
                if (!atenId) return;
                fetch('{{ url('/atendimentos') }}/' + atenId + '/observacoes', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then((r) => r.json())
                    .then((data) => {
                        document.getElementById('aten_obs_tecnica').value = data.aten_obs_tecnica || '';
                        document.getElementById('aten_obs_cliente').value = data.aten_obs_cliente || '';
                        document.getElementById('aten_obs_manutencao').value = data.aten_obs_manutencao || '';
                        const temObs = !!(data.aten_obs_tecnica || data.aten_obs_cliente || data.aten_obs_manutencao);
                        atualizarIconeAba('tab-observacoes-tab', temObs);
                    });
            }

            function carregarEquipamentos(atenId) {
                if (!atenId) return;
                fetch('{{ url('/atendimentos') }}/' + atenId + '/equipamentos', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then((r) => r.json())
                    .then((response) => renderizarEquipamentos(response.equipamentos, atenId));
            }

            function renderizarEquipamentos(equipamentos, atenId) {
                atualizarIconeAba('tab-equipamentos-tab', equipamentos && equipamentos.length > 0);
                const tbody = document.querySelector('#table_equipamentos tbody');
                tbody.innerHTML = '';

                if (!equipamentos || !equipamentos.length) {
                    tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">Nenhum equipamento cadastrado</td></tr>';
                    return;
                }

                equipamentos.forEach((equip) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td class="text-center align-middle">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-equip" data-equip-id="${equip.aten_equip_id}" data-aten-id="${atenId}">
                            <i class="bi bi-trash" aria-hidden="true"></i>
                        </button>
                    </td><td class="align-middle"></td>`;
                    tr.querySelector('td:last-child').textContent = equip.aten_equip_descricao;
                    tbody.appendChild(tr);
                });
            }

            document.getElementById('btnAdicionarEquipamento')?.addEventListener('click', function () {
                const atenId = document.getElementById('aten_id').value;
                if (!atenId) return;

                const fd = new FormData();
                fd.append('aten_equip_descricao', document.getElementById('aten_equip_descricao').value || '');

                fetch('{{ url('/atendimentos') }}/' + atenId + '/equipamentos', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                    body: fd,
                })
                    .then(async (r) => {
                        const payload = await r.json();
                        if (!r.ok) throw payload;
                        return payload;
                    })
                    .then((response) => {
                        document.getElementById('aten_equip_descricao').value = '';
                        mostrarFeedbackModal('success', response.message || 'Equipamento adicionado.');
                        renderizarEquipamentos(response.equipamentos, atenId);
                    })
                    .catch((payload) => {
                        const mensagens = Object.values(payload?.errors || {}).flat();
                        mostrarFeedbackModal('error', mensagens.length ? mensagens.join('<br>') : 'Erro ao adicionar equipamento.');
                    });
            });

            document.querySelector('#table_equipamentos')?.addEventListener('click', function (event) {
                const btn = event.target.closest('.btn-delete-equip');
                if (!btn) return;

                fetch('{{ url('/atendimentos') }}/' + btn.dataset.atenId + '/equipamentos/' + btn.dataset.equipId, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                })
                    .then((r) => r.json())
                    .then((response) => {
                        mostrarFeedbackModal('success', response.message || 'Equipamento removido.');
                        renderizarEquipamentos(response.equipamentos, btn.dataset.atenId);
                    })
                    .catch(() => mostrarFeedbackModal('error', 'Erro ao remover equipamento.'));
            });
            function carregarAnexosAtendimento(atenId) {
                if (!atenId) return;
                fetch('{{ url('/atendimentos') }}/' + atenId + '/anexos', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then((r) => r.json())
                    .then((r) => renderizarAnexosAtendimento(r.anexos, atenId));
            }

            function renderizarAnexosAtendimento(anexos, atenId) {
                atualizarIconeAba('tab-anexos-aten-tab', anexos && anexos.length > 0);
                const container = document.getElementById('aten_anexos_lista');
                container.innerHTML = '';

                if (!anexos || !anexos.length) {
                    container.innerHTML = '<p class="text-muted text-center">Nenhum anexo cadastrado.</p>';
                    return;
                }

                const ul = document.createElement('ul');
                ul.className = 'list-group';
                anexos.forEach((a) => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item d-flex justify-content-between align-items-center';
                    const link = document.createElement('a');
                    link.href = '/midia/' + a.aten_anexo_path;
                    link.target = '_blank';
                    link.textContent = a.aten_anexo_nome_original;
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn btn-outline-danger btn-sm btn-delete-anexo-aten';
                    btn.dataset.id = a.aten_anexo_id;
                    btn.dataset.atenId = atenId;
                    btn.innerHTML = '<i class="bi bi-trash" aria-hidden="true"></i>';
                    li.append(link, btn);
                    ul.appendChild(li);
                });
                container.appendChild(ul);
            }

            document.getElementById('aten_anexo_file')?.addEventListener('change', function () {
                const names = Array.from(this.files).map((f) => f.name).join(', ');
                document.getElementById('aten_anexo_nome').textContent = names || 'Selecione arquivos para upload';
            });

            document.getElementById('btnUploadAnexoAten')?.addEventListener('click', function () {
                const atenId = document.getElementById('aten_id').value;
                const files = document.getElementById('aten_anexo_file').files;
                if (!atenId || !files.length) return;

                const fd = new FormData();
                Array.from(files).forEach((f) => fd.append('arquivos[]', f));

                fetch('{{ url('/atendimentos') }}/' + atenId + '/upload-anexos', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                    body: fd,
                })
                    .then(async (r) => {
                        const payload = await r.json();
                        if (!r.ok) throw payload;
                        return payload;
                    })
                    .then((response) => {
                        mostrarFeedbackModal('success', response.message || 'Arquivo(s) enviado(s).');
                        document.getElementById('aten_anexo_file').value = '';
                        document.getElementById('aten_anexo_nome').textContent = 'Selecione arquivos para upload';
                        carregarAnexosAtendimento(atenId);
                    })
                    .catch((payload) => mostrarFeedbackModal('error', payload?.message || 'Erro ao enviar arquivo(s).'));
            });

            document.getElementById('aten_anexos_lista')?.addEventListener('click', function (event) {
                const btn = event.target.closest('.btn-delete-anexo-aten');
                if (!btn) return;

                fetch('{{ url('/atendimentos') }}/' + btn.dataset.atenId + '/anexos/' + btn.dataset.id, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                })
                    .then((r) => r.json())
                    .then((response) => {
                        mostrarFeedbackModal('success', response.message || 'Anexo removido.');
                        carregarAnexosAtendimento(btn.dataset.atenId);
                    })
                    .catch(() => mostrarFeedbackModal('error', 'Erro ao remover anexo.'));
            });

            {{-- BF03 — resumo do cliente vinculado, sem navegação extra. --}}
            function carregarResumoCliente(clienteId) {
                const box = document.getElementById('cliente-resumo-box');
                if (!clienteId) {
                    box.hidden = true;
                    return;
                }
                fetch('{{ url('/clientes') }}/' + clienteId + '/resumo', {
                    headers: { 'Accept': 'application/json' },
                })
                    .then((r) => r.ok ? r.json() : Promise.reject())
                    .then((d) => {
                        document.getElementById('cliente-resumo-segmento').textContent = d.cli_segmento || '—';
                        document.getElementById('cliente-resumo-classificacao').textContent = d.classificacao || '—';
                        document.getElementById('cliente-resumo-contato').textContent = d.cli_contato_principal || '—';
                        document.getElementById('cliente-resumo-cidade').textContent = [d.cli_cidade, d.cli_uf].filter(Boolean).join('/') || '—';
                        box.hidden = false;
                    })
                    .catch(() => { box.hidden = true; });
            }

            document.addEventListener('DOMContentLoaded', function () {
                window.setupAutocomplete('#aten_cliente_nome', '#aten_cliente_id', '{{ route('clientes.autocomplete') }}', {
                    onSelect: (item) => carregarResumoCliente(item.id),
                });

                @if($editando)
                    atualizarLinkWhatsapp(@json($atendimento->aten_telefone));
                    carregarResumoCliente(@json($atendimento->aten_cliente_id));
                    carregarObservacoes(@json($atendimento->aten_id));
                    carregarEquipamentos(@json($atendimento->aten_id));
                    carregarAnexosAtendimento(@json($atendimento->aten_id));
                @endif
            });
        </script>
    @endpush
</x-layout>