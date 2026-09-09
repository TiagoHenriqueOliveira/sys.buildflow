<x-layout title="Naturezas de Atendimento">
    <div
        x-data="{
            aberto: {{ $errors->any() ? 'true' : 'false' }},
            editando: {{ old('nat_aten_id') ? 'true' : 'false' }},
        }"
    >
        <div class="sbadmin-page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h2 class="sbadmin-page-heading">Naturezas de Atendimento</h2>
                <p class="sbadmin-page-subheading">Gerencie as naturezas de atendimento cadastradas no sistema.</p>
            </div>
            <button
                type="button"
                class="btn btn-primary sbadmin-btn-primary"
                @click="editando = false; aberto = true; resetFormularioNaturezaAtendimento()"
            >
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Cadastrar
            </button>
        </div>

        @if(session('success'))
            <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
        @endif

        <form method="GET" action="{{ route('naturezas-dos-atendimentos.index') }}" class="sbadmin-card mb-4">
            <div class="sbadmin-card-body d-flex flex-wrap gap-2 align-items-end">
                <div class="flex-grow-1" style="min-width: 240px;">
                    <label for="busca" class="sbadmin-form-label">Buscar</label>
                    <input
                        type="text"
                        id="busca"
                        name="busca"
                        value="{{ $busca }}"
                        class="form-control sbadmin-form-control"
                        placeholder="Descrição"
                    >
                </div>
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="bi bi-search" aria-hidden="true"></i> Buscar
                </button>
                @if($busca !== '')
                    <a href="{{ route('naturezas-dos-atendimentos.index') }}" class="btn btn-link">Limpar</a>
                @endif
            </div>
        </form>

        <x-sbadmin::table
            :headers="['Ações', 'Descrição', 'Modelo de Relatório', 'Status']"
            :paginator="$naturezas"
            :count="$naturezas->count()"
            empty-message="Nenhuma natureza de atendimento encontrada."
        >
            @foreach($naturezas as $n)
                <tr>
                    <td class="text-center">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            data-id="{{ $n->nat_aten_id }}"
                            data-descricao="{{ e($n->nat_aten_descricao) }}"
                            data-mod-rel="{{ (int) $n->nat_aten_mod_relatorio_id }}"
                            data-ativo="{{ (int) $n->nat_aten_ativo }}"
                            aria-label="Editar {{ e($n->nat_aten_descricao) }}"
                            @click="editando = true; aberto = true; preencherFormularioNaturezaAtendimento($el.dataset)"
                        >
                            <i class="bi bi-pencil" aria-hidden="true"></i>
                        </button>
                    </td>
                    <td>{{ $n->nat_aten_descricao }}</td>
                    <td>{{ optional($n->modeloRelatorio)->mod_rel_descricao }}</td>
                    <td>
                        <x-sbadmin::badge :type="$n->nat_aten_ativo ? 'success' : 'error'">
                            {{ $n->nat_aten_ativo ? 'Ativo' : 'Desativado' }}
                        </x-sbadmin::badge>
                    </td>
                </tr>
            @endforeach
        </x-sbadmin::table>

        @include('naturezas_atendimentos.modal')
    </div>

    @push('scripts')
        <script>
            function preencherFormularioNaturezaAtendimento(data) {
                document.getElementById('nat_aten_id').value = data.id || '';
                document.getElementById('nat_aten_descricao').value = data.descricao || '';
                document.getElementById('nat_aten_mod_relatorio_id').value = data.modRel || '';
                document.getElementById('nat_aten_ativo').checked = data.ativo === '1';

                document.getElementById('nat_aten_method').value = 'PUT';
                document.getElementById('form_natureza_atendimento').action = '{{ url('/naturezas-dos-atendimentos') }}/' + data.id;
            }

            function resetFormularioNaturezaAtendimento() {
                const form = document.getElementById('form_natureza_atendimento');
                form.reset();

                document.getElementById('nat_aten_id').value = '';
                document.getElementById('nat_aten_ativo').checked = true;
                document.getElementById('nat_aten_method').value = 'POST';
                form.action = '{{ route('naturezas-dos-atendimentos.store') }}';
            }
        </script>
    @endpush
</x-layout>
