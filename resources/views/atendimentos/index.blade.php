<x-layout title="Atendimentos">
    <div
        id="atendimento-root"
        x-data="{
            aberto: false,
            editando: false,
            tab: 'dados',
            entregaTecnica: false,
        }"
    >
        <div class="sbadmin-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="sbadmin-page-heading">Atendimentos</h2>
                <p class="sbadmin-page-subheading">Gerencie os atendimentos técnicos cadastrados no sistema.</p>
            </div>
            @if(auth()->user()->user_nivel_acesso === 0)
                <button
                    type="button"
                    class="btn btn-primary sbadmin-btn-primary"
                    @click="editando = false; aberto = true; tab = 'dados'; entregaTecnica = false; resetFormularioAtendimento()"
                >
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Cadastrar
                </button>
            @endif
        </div>

        @if(session('success'))
            <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
        @endif

        <form method="GET" action="{{ route('atendimentos.index') }}" class="sbadmin-card mb-4">
            {{-- Filtros individuais por coluna — combinaveis entre si (AND:
                 cada filtro preenchido restringe ainda mais o resultado).
                 Substituem a busca unica que existia antes (removida a
                 pedido do cliente). --}}
            <div class="sbadmin-card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-2">
                        <label for="f_natureza" class="sbadmin-form-label">Natureza</label>
                        <select id="f_natureza" name="f_natureza" class="form-select sbadmin-form-control">
                            <option value="">Todas</option>
                            @foreach($naturezasAtendimentos as $natureza)
                                <option value="{{ $natureza->nat_aten_id }}" @selected((string) $filtroNatureza === (string) $natureza->nat_aten_id)>{{ $natureza->nat_aten_descricao }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="f_tecnico" class="sbadmin-form-label">Técnico</label>
                        <input type="text" id="f_tecnico" name="f_tecnico" value="{{ $filtroTecnico }}" class="form-control sbadmin-form-control" placeholder="Técnico">
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="f_cliente" class="sbadmin-form-label">Cliente</label>
                        <input type="text" id="f_cliente" name="f_cliente" value="{{ $filtroCliente }}" class="form-control sbadmin-form-control" placeholder="Cliente">
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="f_nr_proposta" class="sbadmin-form-label">Nº Proposta</label>
                        <input type="text" id="f_nr_proposta" name="f_nr_proposta" value="{{ $filtroNrProposta }}" class="form-control sbadmin-form-control" placeholder="Nº Proposta">
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="f_periodo_de" class="sbadmin-form-label">Período de</label>
                        <input type="date" id="f_periodo_de" name="f_periodo_de" value="{{ $filtroPeriodoDe }}" class="form-control sbadmin-form-control">
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="f_periodo_ate" class="sbadmin-form-label">Período até</label>
                        <input type="date" id="f_periodo_ate" name="f_periodo_ate" value="{{ $filtroPeriodoAte }}" class="form-control sbadmin-form-control">
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="f_status" class="sbadmin-form-label">Status</label>
                        <select id="f_status" name="f_status" class="form-select sbadmin-form-control">
                            <option value="">Todos</option>
                            @foreach(\App\Enums\AtendimentoStatus::cases() as $statusOpcao)
                                <option value="{{ $statusOpcao->value }}" @selected($filtroStatus === (string) $statusOpcao->value)>{{ $statusOpcao->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-info">
                            <i class="bi bi-funnel" aria-hidden="true"></i> Aplicar
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <x-sbadmin::table
            :headers="auth()->user()->user_nivel_acesso === 0
                ? ['Ações', 'Natureza', 'Técnico', 'Cliente', 'Nº Proposta', 'Período', 'Status']
                : ['Natureza', 'Técnico', 'Cliente', 'Nº Proposta', 'Período', 'Status']"
            :paginator="$atendimentos"
            :count="$atendimentos->count()"
            empty-message="Nenhum atendimento encontrado."
        >
            @php
                $hoje = \Illuminate\Support\Carbon::today();
            @endphp
            @foreach($atendimentos as $a)
                @php
                    $status = \App\Enums\AtendimentoStatus::tryFrom($a->aten_status);
                    $atrasado = (int) $a->aten_status !== 3 && $a->aten_dt_fim->lt($hoje);
                @endphp
                <tr class="{{ $atrasado ? 'table-danger' : '' }}">
                    @if(auth()->user()->user_nivel_acesso === 0)
                        <td class="text-center">
                            <button
                                type="button"
                                class="btn btn-sm sbadmin-table-action-btn"
                                data-id="{{ $a->aten_id }}"
                                data-natureza-id="{{ $a->aten_natureza_id }}"
                                data-cliente-id="{{ $a->aten_cliente_id }}"
                                data-cliente="{{ e(optional($a->cliente)->cli_nome) }}"
                                data-usuario-id="{{ $a->aten_usuario_id }}"
                                data-status="{{ (int) $a->aten_status }}"
                                data-nr-proposta="{{ e($a->aten_nr_proposta) }}"
                                data-contato="{{ e($a->aten_contato) }}"
                                data-responsavel="{{ e($a->aten_responsavel) }}"
                                data-telefone="{{ e($a->aten_telefone) }}"
                                data-entrega-tecnica="{{ (int) $a->aten_entrega_tecnica }}"
                                data-endereco="{{ e($a->aten_endereco) }}"
                                data-dt-inicio="{{ $a->aten_dt_inicio->format('Y-m-d') }}"
                                data-dt-fim="{{ $a->aten_dt_fim->format('Y-m-d') }}"
                                aria-label="Editar atendimento"
                                @click="
                                    editando = true; aberto = true; tab = 'dados';
                                    entregaTecnica = $el.dataset.entregaTecnica === '1';
                                    preencherFormularioAtendimento($el.dataset)
                                "
                            >
                                <i class="bi bi-pencil" aria-hidden="true"></i>
                            </button>
                        </td>
                    @endif
                    <td>{{ optional($a->natureza)->nat_aten_descricao }}</td>
                    <td>{{ optional($a->usuario)->user_nome }}</td>
                    <td>{{ optional($a->cliente)->cli_nome }}</td>
                    <td>{{ $a->aten_nr_proposta }}</td>
                    <td>{{ $a->aten_dt_inicio->format('d/m/Y') }} - {{ $a->aten_dt_fim->format('d/m/Y') }}</td>
                    <td>
                        @if($status)
                            <x-sbadmin::badge :type="match($status) {
                                \App\Enums\AtendimentoStatus::Concluida => 'success',
                                \App\Enums\AtendimentoStatus::Paralisada => 'warning',
                                \App\Enums\AtendimentoStatus::EmAndamento => 'info',
                                default => 'neutral',
                            }">
                                {{ $status->label() }}
                            </x-sbadmin::badge>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-sbadmin::table>

        @if(auth()->user()->user_nivel_acesso === 0)
            @include('atendimentos.modal')
        @endif
    </div>

    @push('scripts')
        <script>
            // Estado "sujo" do modal de atendimento: qualquer criação/edição
            // salva com sucesso (dados, observações, equipamentos ou anexos)
            // marca a flag; ao fechar o modal com a flag marcada, a página
            // recarrega pra listagem paginada refletir a mudança (antes a
            // DataTable fazia isso em background via ajax.reload() sem
            // recarregar a página — sem DataTable, recarregar ao fechar é a
            // forma mínima de manter a lista atualizada).
            window.__atendimentoState = { dirty: false };

            function fecharModalAtendimento() {
                if (window.__atendimentoState.dirty) {
                    window.location.reload();
                    return;
                }
                Alpine.$data(document.getElementById('atendimento-root')).aberto = false;
            }

            function preencherFormularioAtendimento(data) {
                document.getElementById('aten_id').value = data.id || '';
                document.getElementById('aten_natureza_id').value = data.naturezaId || '';
                document.getElementById('aten_cliente_id').value = data.clienteId || '';
                document.getElementById('aten_cliente_nome').value = data.cliente || '';
                document.getElementById('aten_usuario_id').value = data.usuarioId || '';
                document.getElementById('aten_nr_proposta').value = data.nrProposta || '';
                document.getElementById('aten_contato').value = data.contato || '';
                document.getElementById('aten_responsavel').value = data.responsavel || '';
                const telefone = data.telefone || '';
                document.getElementById('aten_telefone').value = telefone;
                atualizarLinkWhatsapp(telefone);
                document.getElementById('aten_endereco').value = data.endereco || '';
                document.getElementById('aten_dt_inicio').value = data.dtInicio || '';
                document.getElementById('aten_dt_fim').value = data.dtFim || '';

                document.querySelectorAll('input[name="aten_status"]').forEach((el) => {
                    el.checked = el.value === String(data.status ?? '0');
                });

                document.getElementById('aten_method').value = 'PUT';
                document.getElementById('form_atendimento').action = '{{ url('/atendimentos') }}/' + data.id;

                habilitarAbas(true);
                carregarObservacoes(data.id);
                carregarEquipamentos(data.id);
                carregarAnexosAtendimento(data.id);
            }

            function resetFormularioAtendimento() {
                const form = document.getElementById('form_atendimento');
                form.reset();
                window.__atendimentoState.dirty = false;

                document.getElementById('aten_id').value = '';
                document.getElementById('aten_cliente_id').value = '';
                document.getElementById('aten_method').value = 'POST';
                form.action = '{{ route('atendimentos.store') }}';

                document.querySelectorAll('input[name="aten_status"]').forEach((el) => {
                    el.checked = el.value === '0';
                });

                atualizarLinkWhatsapp('');
                habilitarAbas(false);
                document.getElementById('aten_obs_tecnica').value = '';
                document.getElementById('aten_obs_cliente').value = '';
                document.getElementById('aten_obs_manutencao').value = '';
                document.getElementById('aten_equip_descricao').value = '';
                document.querySelector('#table_equipamentos tbody').innerHTML = '';
                document.getElementById('aten_anexos_lista').innerHTML = '';
                limparIconesAbas();
            }

            function habilitarAbas(habilitar) {
                document.querySelectorAll('.aten-tab-restrita').forEach((el) => {
                    el.disabled = !habilitar;
                    el.classList.toggle('disabled', !habilitar);
                });
            }

            function limparIconesAbas() {
                ['tab-observacoes-tab', 'tab-equipamentos-tab', 'tab-anexos-aten-tab'].forEach((id) => atualizarIconeAba(id, false));
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
                const btnSubmit = form.querySelector('button[type="submit"]');
                btnSubmit.disabled = true;

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
                        window.__atendimentoState.dirty = true;
                        mostrarFeedbackModal('success', response.message || 'Salvo com sucesso.');
                        btnSubmit.disabled = false;

                        if (response.aten_id && response.atendimento) {
                            Alpine.$data(document.getElementById('atendimento-root')).editando = true;
                            preencherFormularioAtendimento({
                                id: response.atendimento.aten_id,
                                naturezaId: response.atendimento.aten_natureza_id,
                                clienteId: response.atendimento.aten_cliente_id,
                                cliente: response.atendimento.aten_cliente_nome,
                                usuarioId: response.atendimento.aten_usuario_id,
                                status: response.atendimento.aten_status,
                                nrProposta: response.atendimento.aten_nr_proposta,
                                contato: response.atendimento.aten_contato,
                                responsavel: response.atendimento.aten_responsavel,
                                telefone: response.atendimento.aten_telefone,
                                endereco: response.atendimento.aten_endereco,
                                dtInicio: response.atendimento.aten_dt_inicio,
                                dtFim: response.atendimento.aten_dt_fim,
                            });
                        } else {
                            carregarObservacoes(document.getElementById('aten_id').value);
                        }
                    })
                    .catch((payload) => {
                        btnSubmit.disabled = false;
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
                    tr.innerHTML = `<td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-equip" data-equip-id="${equip.aten_equip_id}" data-aten-id="${atenId}">
                            <i class="bi bi-trash" aria-hidden="true"></i>
                        </button>
                    </td><td></td>`;
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
                        window.__atendimentoState.dirty = true;
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
                        window.__atendimentoState.dirty = true;
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
                        window.__atendimentoState.dirty = true;
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
                        window.__atendimentoState.dirty = true;
                        mostrarFeedbackModal('success', response.message || 'Anexo removido.');
                        carregarAnexosAtendimento(btn.dataset.atenId);
                    })
                    .catch(() => mostrarFeedbackModal('error', 'Erro ao remover anexo.'));
            });

            {{-- Autocomplete de cliente — ver resources/js/autocomplete.js --}}
            document.addEventListener('DOMContentLoaded', function () {
                window.setupAutocomplete('#aten_cliente_nome', '#aten_cliente_id', '{{ route('clientes.autocomplete') }}');
            });
        </script>
    @endpush
</x-layout>
