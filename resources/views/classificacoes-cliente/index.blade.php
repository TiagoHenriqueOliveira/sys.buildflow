<x-layout title="Classificações de Cliente">
    <div
        x-data="{
            aberto: {{ $errors->any() ? 'true' : 'false' }},
            editando: {{ old('cla_cli_id') ? 'true' : 'false' }},
        }"
    >
        <div class="sbadmin-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="sbadmin-page-heading">Classificações de Cliente</h2>
                <p class="sbadmin-page-subheading">Opções de classificação exibidas no cadastro de cliente (aba Dados Gerais).</p>
            </div>
            <button
                type="button"
                class="btn btn-primary sbadmin-btn-primary"
                @click="editando = false; aberto = true; resetFormularioClassificacao()"
            >
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Cadastrar
            </button>
        </div>

        @if(session('success'))
            <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
        @endif

        <form method="GET" action="{{ route('classificacoes-cliente.index') }}" class="sbadmin-card mb-4">
            <div class="sbadmin-card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-4">
                        <label for="f_nome" class="sbadmin-form-label">Nome</label>
                        <input type="text" id="f_nome" name="f_nome" value="{{ $filtroNome }}" class="form-control sbadmin-form-control" placeholder="Nome">
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
            :headers="['Ações', 'Nome', 'Status']"
            :paginator="$classificacoes"
            :count="$classificacoes->count()"
            empty-message="Nenhuma classificação cadastrada."
        >
            @foreach($classificacoes as $c)
                <tr>
                    <td class="text-center">
                        <button
                            type="button"
                            class="btn btn-sm sbadmin-table-action-btn"
                            data-id="{{ $c->cla_cli_id }}"
                            data-nome="{{ e($c->cla_cli_nome) }}"
                            data-ativo="{{ (int) $c->cla_cli_ativo }}"
                            aria-label="Editar {{ e($c->cla_cli_nome) }}"
                            @click="editando = true; aberto = true; preencherFormularioClassificacao($el.dataset)"
                        >
                            <i class="bi bi-pencil" aria-hidden="true"></i>
                        </button>
                    </td>
                    <td>{{ $c->cla_cli_nome }}</td>
                    <td>
                        <x-sbadmin::badge :type="$c->cla_cli_ativo ? 'success' : 'error'">
                            {{ $c->cla_cli_ativo ? 'Ativo' : 'Inativo' }}
                        </x-sbadmin::badge>
                    </td>
                </tr>
            @endforeach
        </x-sbadmin::table>

        @include('classificacoes-cliente.modal')
    </div>

    @push('scripts')
        <script>
            function preencherFormularioClassificacao(data) {
                document.getElementById('cla_cli_id').value = data.id || '';
                document.getElementById('cla_cli_nome').value = data.nome || '';
                document.getElementById('cla_cli_ativo').checked = data.ativo === '1';

                document.getElementById('cla_cli_method').value = 'PUT';
                document.getElementById('form_classificacao').action = '{{ url('/classificacoes-cliente') }}/' + data.id;
            }

            function resetFormularioClassificacao() {
                const form = document.getElementById('form_classificacao');
                form.reset();

                document.getElementById('cla_cli_id').value = '';
                document.getElementById('cla_cli_ativo').checked = true;
                document.getElementById('cla_cli_method').value = 'POST';
                form.action = '{{ route('classificacoes-cliente.store') }}';
            }
        </script>
    @endpush
</x-layout>