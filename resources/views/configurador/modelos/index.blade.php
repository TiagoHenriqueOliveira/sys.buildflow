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

        @if($perguntasDisponiveis->isEmpty())
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
                            data-perguntas="{{ $m->perguntas->pluck('cfg_perg_id')->toJson() }}"
                            data-secoes="{{ json_encode($m->secoesAtivas()) }}"
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
            function preencherFormularioModelo(data) {
                document.getElementById('cfg_mod_id').value = data.id || '';
                document.getElementById('cfg_mod_nome').value = data.nome || '';
                document.getElementById('cfg_mod_setor').value = data.setor || '';
                document.getElementById('cfg_mod_ativo').checked = data.ativo === '1';

                const selecionadas = JSON.parse(data.perguntas || '[]').map(String);
                document.querySelectorAll('.pergunta-checkbox').forEach((el) => {
                    el.checked = selecionadas.includes(el.value);
                });

                const secoes = JSON.parse(data.secoes || '{}');
                document.getElementById('cfg_mod_usa_horarios').checked = !!secoes.horarios;
                document.getElementById('cfg_mod_usa_clima').checked = !!secoes.clima;
                document.getElementById('cfg_mod_usa_servicos').checked = !!secoes.servicos;
                document.getElementById('cfg_mod_usa_pecas').checked = !!secoes.pecas;
                document.getElementById('cfg_mod_usa_ocorrencias').checked = !!secoes.ocorrencias;
                document.getElementById('cfg_mod_usa_observacoes').checked = !!secoes.observacoes;
                window.atualizarSecoesRelatorioVisiveis(data.setor);

                document.getElementById('cfg_mod_method').value = 'PUT';
                document.getElementById('form_modelo').action = '{{ url('/configurador/modelos') }}/' + data.id;
            }

            function resetFormularioModelo() {
                const form = document.getElementById('form_modelo');
                form.reset();

                document.getElementById('cfg_mod_id').value = '';
                document.getElementById('cfg_mod_ativo').checked = true;
                document.querySelectorAll('.pergunta-checkbox').forEach((el) => { el.checked = false; });
                document.querySelectorAll('#secoesRelatorioBox input[type="checkbox"]').forEach((el) => { el.checked = true; });
                window.atualizarSecoesRelatorioVisiveis('');

                document.getElementById('cfg_mod_method').value = 'POST';
                form.action = '{{ route('configurador.modelos.store') }}';
            }

            // Setor "Assistência" (1) usa as secoes do relatorio; "Comercial" (0)
            // nao tem essas abas - ver ConfigModelo::secoesAtivas().
            window.atualizarSecoesRelatorioVisiveis = function (setorValue) {
                document.getElementById('secoesRelatorioBox').hidden = String(setorValue) !== '1';
            };
        </script>
    @endpush
</x-layout>