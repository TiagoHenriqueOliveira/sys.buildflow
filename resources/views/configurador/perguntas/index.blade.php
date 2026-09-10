<x-layout title="Configurador | Perguntas">
    <div
        id="configurador-perguntas-root"
        x-data="{
            aberto: {{ $errors->any() ? 'true' : 'false' }},
            editando: {{ old('cfg_perg_id') ? 'true' : 'false' }},
            tipo: {{ (int) old('cfg_perg_tipo', 2) }},
            opcoes: {{ Illuminate\Support\Js::from(old('opcoes', [])) }},
            addOpcao() { this.opcoes.push({ texto: '' }); },
            removerOpcao(i) { this.opcoes.splice(i, 1); },
        }"
    >
        <div class="sbadmin-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="sbadmin-page-heading">Configurador | Perguntas</h2>
                <p class="sbadmin-page-subheading">Banco de perguntas reutilizável entre modelos de orçamento e de relatório.</p>
            </div>
            <button
                type="button"
                class="btn btn-primary sbadmin-btn-primary"
                @click="editando = false; aberto = true; resetFormularioPergunta()"
            >
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Cadastrar
            </button>
        </div>

        @if(session('success'))
            <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
        @endif

        <form method="GET" action="{{ route('configurador.perguntas.index') }}" class="sbadmin-card mb-4">
            <div class="sbadmin-card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-6">
                        <label for="f_texto" class="sbadmin-form-label">Texto</label>
                        <input type="text" id="f_texto" name="f_texto" value="{{ $filtroTexto }}" class="form-control sbadmin-form-control" placeholder="Texto da pergunta">
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="f_tipo" class="sbadmin-form-label">Tipo</label>
                        <select id="f_tipo" name="f_tipo" class="form-select sbadmin-form-control">
                            <option value="">Todos</option>
                            @foreach($tiposPergunta as $t)
                                <option value="{{ $t->value }}" @selected((string) $filtroTipo === (string) $t->value)>{{ $t->label() }}</option>
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
            :headers="['Ações', 'Texto', 'Tipo', 'Anexo', 'Status']"
            :paginator="$perguntas"
            :count="$perguntas->count()"
            empty-message="Nenhuma pergunta cadastrada."
        >
            @foreach($perguntas as $p)
                <tr class="{{ $p->cfg_perg_ativo ? '' : 'table-danger' }}">
                    <td class="text-center">
                        <button
                            type="button"
                            class="btn btn-sm sbadmin-table-action-btn"
                            data-id="{{ $p->cfg_perg_id }}"
                            data-texto="{{ e($p->cfg_perg_texto) }}"
                            data-tipo="{{ $p->cfg_perg_tipo->value }}"
                            data-permite-anexo="{{ (int) $p->cfg_perg_permite_anexo }}"
                            data-ativo="{{ (int) $p->cfg_perg_ativo }}"
                            data-opcoes="{{ $p->opcoes->map(fn ($o) => ['texto' => $o->cfg_perg_op_texto])->toJson() }}"
                            aria-label="Editar pergunta"
                            @click="editando = true; aberto = true; preencherFormularioPergunta($el.dataset)"
                        >
                            <i class="bi bi-pencil" aria-hidden="true"></i>
                        </button>
                    </td>
                    <td>{{ \Illuminate\Support\Str::limit($p->cfg_perg_texto, 80) }}</td>
                    <td>{{ $p->cfg_perg_tipo->label() }}</td>
                    <td>
                        <x-sbadmin::badge :type="$p->cfg_perg_permite_anexo ? 'info' : 'neutral'">
                            {{ $p->cfg_perg_permite_anexo ? 'Sim' : 'Não' }}
                        </x-sbadmin::badge>
                    </td>
                    <td>
                        <x-sbadmin::badge :type="$p->cfg_perg_ativo ? 'success' : 'error'">
                            {{ $p->cfg_perg_ativo ? 'Ativo' : 'Inativo' }}
                        </x-sbadmin::badge>
                    </td>
                </tr>
            @endforeach
        </x-sbadmin::table>

        @include('configurador.perguntas.modal')
    </div>

    @push('scripts')
        <script>
            function preencherFormularioPergunta(data) {
                document.getElementById('cfg_perg_id').value = data.id || '';
                document.getElementById('cfg_perg_texto').value = data.texto || '';
                document.getElementById('cfg_perg_ativo').checked = data.ativo === '1';
                document.getElementById('cfg_perg_permite_anexo').checked = data.permiteAnexo === '1';

                const root = document.querySelector('#configurador-perguntas-root');
                const alpine = Alpine.$data(root);
                alpine.tipo = parseInt(data.tipo || '2', 10);
                alpine.opcoes = JSON.parse(data.opcoes || '[]');

                document.getElementById('cfg_perg_method').value = 'PUT';
                document.getElementById('form_pergunta').action = '{{ url('/configurador/perguntas') }}/' + data.id;
            }

            function resetFormularioPergunta() {
                const form = document.getElementById('form_pergunta');
                form.reset();

                document.getElementById('cfg_perg_id').value = '';
                document.getElementById('cfg_perg_ativo').checked = true;

                const root = document.querySelector('#configurador-perguntas-root');
                const alpine = Alpine.$data(root);
                alpine.tipo = 2;
                alpine.opcoes = [];

                document.getElementById('cfg_perg_method').value = 'POST';
                form.action = '{{ route('configurador.perguntas.store') }}';
            }
        </script>
    @endpush
</x-layout>