<x-layout title="Configurador | Modelos">
    <div
        id="configurador-modelos-root"
        x-data="{
            aberto: {{ $errors->any() ? 'true' : 'false' }},
            editando: {{ old('cfg_mod_id') ? 'true' : 'false' }},
        }"
    >
        <div class="sbadmin-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="sbadmin-page-heading">Configurador | Modelos</h2>
                <p class="sbadmin-page-subheading">Agrupe perguntas em modelos reutilizáveis por setor (Comercial ou Assistência).</p>
            </div>
            <button
                type="button"
                class="btn btn-primary sbadmin-btn-primary"
                @click="editando = false; aberto = true; resetFormularioModelo()"
            >
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Cadastrar
            </button>
        </div>

        @if(session('success'))
            <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
        @endif

        @if(!$temPerguntasCadastradas)
            <x-sbadmin::alert type="warning">
                Nenhuma pergunta cadastrada ainda. <a href="{{ route('configurador.perguntas.index') }}">Cadastre perguntas no Configurador</a> antes de montar um modelo.
            </x-sbadmin::alert>
        @endif

        <form method="GET" action="{{ route('configurador.modelos.index') }}" class="sbadmin-card mb-4">
            <div class="sbadmin-card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-6">
                        <label for="f_nome" class="sbadmin-form-label">Nome</label>
                        <input type="text" id="f_nome" name="f_nome" value="{{ $filtroNome }}" class="form-control sbadmin-form-control" placeholder="Nome do modelo">
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="f_setor" class="sbadmin-form-label">Setor</label>
                        <select id="f_setor" name="f_setor" class="form-select sbadmin-form-control">
                            <option value="">Todos</option>
                            @foreach($setores as $s)
                                <option value="{{ $s->value }}" @selected((string) $filtroSetor === (string) $s->value)>{{ $s->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-info">
                            <i class="bi bi-funnel" aria-hidden="true"></i> Aplicar
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <x-sbadmin::table
            :headers="['Ações', 'Nome', 'Setor', 'Perguntas', 'Status']"
            :paginator="$modelos"
            :count="$modelos->count()"
            empty-message="Nenhum modelo cadastrado."
        >
            @foreach($modelos as $m)
                <tr class="{{ $m->cfg_mod_ativo ? '' : 'table-danger' }}">
                    <td class="text-center">
                        <button
                            type="button"
                            class="btn btn-sm sbadmin-table-action-btn"
                            data-id="{{ $m->cfg_mod_id }}"
                            data-nome="{{ e($m->cfg_mod_nome) }}"
                            data-setor="{{ $m->cfg_mod_setor->value }}"
                            data-ativo="{{ (int) $m->cfg_mod_ativo }}"
                            data-perguntas="{{ $m->perguntas->map(fn($p) => ['id' => $p->cfg_perg_id, 'texto' => $p->cfg_perg_texto, 'tipo' => $p->cfg_perg_tipo->label()])->toJson() }}"
                            aria-label="Editar {{ e($m->cfg_mod_nome) }}"
                            @click="editando = true; aberto = true; preencherFormularioModelo($el.dataset)"
                        >
                            <i class="bi bi-pencil" aria-hidden="true"></i>
                        </button>
                    </td>
                    <td>{{ $m->cfg_mod_nome }}</td>
                    <td>{{ $m->cfg_mod_setor->label() }}</td>
                    <td>{{ $m->perguntas->count() }}</td>
                    <td>
                        <x-sbadmin::badge :type="$m->cfg_mod_ativo ? 'success' : 'error'">
                            {{ $m->cfg_mod_ativo ? 'Ativo' : 'Inativo' }}
                        </x-sbadmin::badge>
                    </td>
                </tr>
            @endforeach
        </x-sbadmin::table>

        @include('configurador.modelos.modal')
    </div>

    @push('scripts')
        <script>
            // ─── Perguntas (autocomplete - ~500 cadastradas, checklist estatico
            // nao escala) ────────────────────────────────────────────────────────
            let perguntasSelecionadas = @json($perguntasAntigas).map((p) => ({ id: String(p.id), texto: p.texto, tipo: p.tipo }));

            function renderizarPerguntasSelecionadas() {
                const container = document.getElementById('perguntasSelecionadasContainer');
                container.innerHTML = '';
                if (!perguntasSelecionadas.length) {
                    container.innerHTML = '<p class="text-body-secondary small mb-0" id="perguntasVazioMsg">Nenhuma pergunta adicionada ainda.</p>';
                    return;
                }
                perguntasSelecionadas.forEach((p, index) => {
                    const row = document.createElement('div');
                    row.className = 'd-flex align-items-center justify-content-between border-bottom py-1';
                    row.innerHTML = '<span></span><input type="hidden" name="perguntas[]" value="' + p.id + '">' +
                        '<button type="button" class="btn btn-outline-danger btn-sm" aria-label="Remover pergunta"><i class="bi bi-trash" aria-hidden="true"></i></button>';
                    row.querySelector('span').textContent = p.texto + (p.tipo ? ' (' + p.tipo + ')' : '');
                    row.querySelector('button').addEventListener('click', function () {
                        perguntasSelecionadas.splice(index, 1);
                        renderizarPerguntasSelecionadas();
                    });
                    container.appendChild(row);
                });
            }

            function adicionarPerguntaSelecionada(p) {
                p = { id: String(p.id), texto: p.texto, tipo: p.tipo };
                if (perguntasSelecionadas.some((sel) => sel.id === p.id)) return;
                perguntasSelecionadas.push(p);
                renderizarPerguntasSelecionadas();
            }

            document.addEventListener('DOMContentLoaded', renderizarPerguntasSelecionadas);

            (function configurarBuscaDePerguntas() {
                const input = document.getElementById('pergunta_busca');
                const lista = document.createElement('ul');
                lista.className = 'sbadmin-autocomplete-list';
                lista.hidden = true;
                const pai = input.parentElement;
                if (getComputedStyle(pai).position === 'static') pai.style.position = 'relative';
                input.insertAdjacentElement('afterend', lista);

                let timer = null;
                let controller = null;

                function esconder() {
                    lista.hidden = true;
                    lista.innerHTML = '';
                }

                function mostrar(itens) {
                    lista.innerHTML = '';
                    if (!itens || !itens.length) { esconder(); return; }
                    itens.forEach((item) => {
                        const li = document.createElement('li');
                        li.textContent = item.texto + ' (' + item.tipo + ')';
                        li.addEventListener('mousedown', function (event) {
                            event.preventDefault();
                            adicionarPerguntaSelecionada(item);
                            input.value = '';
                            esconder();
                        });
                        lista.appendChild(li);
                    });
                    lista.hidden = false;
                }

                input.addEventListener('input', function () {
                    clearTimeout(timer);
                    if (controller) controller.abort();
                    const termo = input.value.trim();
                    if (termo.length < 2) { esconder(); return; }
                    timer = setTimeout(function () {
                        controller = new AbortController();
                        fetch('{{ route('configurador.perguntas.autocomplete') }}?term=' + encodeURIComponent(termo), {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            signal: controller.signal,
                        })
                            .then((r) => r.json())
                            .then(mostrar)
                            .catch((e) => { if (e.name !== 'AbortError') esconder(); });
                    }, 250);
                });
                input.addEventListener('blur', () => setTimeout(esconder, 150));
                document.addEventListener('click', function (event) {
                    if (event.target !== input && !lista.contains(event.target)) esconder();
                });
            })();

            function preencherFormularioModelo(data) {
                document.getElementById('cfg_mod_id').value = data.id || '';
                document.getElementById('cfg_mod_nome').value = data.nome || '';
                document.getElementById('cfg_mod_setor').value = data.setor || '';
                document.getElementById('cfg_mod_ativo').checked = data.ativo === '1';

                perguntasSelecionadas = JSON.parse(data.perguntas || '[]').map((p) => ({ id: String(p.id), texto: p.texto, tipo: p.tipo }));
                renderizarPerguntasSelecionadas();

                document.getElementById('cfg_mod_method').value = 'PUT';
                document.getElementById('form_modelo').action = '{{ url('/configurador/modelos') }}/' + data.id;
            }

            function resetFormularioModelo() {
                const form = document.getElementById('form_modelo');
                form.reset();

                document.getElementById('cfg_mod_id').value = '';
                document.getElementById('cfg_mod_ativo').checked = true;
                perguntasSelecionadas = [];
                renderizarPerguntasSelecionadas();

                document.getElementById('cfg_mod_method').value = 'POST';
                form.action = '{{ route('configurador.modelos.store') }}';
            }
        </script>
    @endpush
</x-layout>