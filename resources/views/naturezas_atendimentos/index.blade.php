<x-layout title="Naturezas de Atendimento">
    <div
        x-data="{
            aberto: {{ $errors->any() ? 'true' : 'false' }},
            editando: {{ old('nat_aten_id') ? 'true' : 'false' }},
        }"
    >
        <div class="sbadmin-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
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
            {{-- Filtro individual por coluna — substitui a busca unica que
                 existia antes (removida a pedido do cliente). --}}
            <div class="sbadmin-card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-4">
                        <label for="f_descricao" class="sbadmin-form-label">Descrição</label>
                        <input type="text" id="f_descricao" name="f_descricao" value="{{ $filtroDescricao }}" class="form-control sbadmin-form-control" placeholder="Descrição">
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
            :headers="['Ações', 'Descrição', 'Modelo de Relatório', 'Modelo (Configurador)', 'Status']"
            :paginator="$naturezas"
            :count="$naturezas->count()"
            empty-message="Nenhuma natureza de atendimento encontrada."
        >
            @foreach($naturezas as $n)
                <tr>
                    <td class="text-center">
                        <button
                            type="button"
                            class="btn btn-sm sbadmin-table-action-btn"
                            data-id="{{ $n->nat_aten_id }}"
                            data-descricao="{{ e($n->nat_aten_descricao) }}"
                            data-mod-rel="{{ (int) $n->nat_aten_mod_relatorio_id }}"
                            data-config-modelo="{{ (int) $n->nat_aten_config_modelo_id }}"
                            data-ativo="{{ (int) $n->nat_aten_ativo }}"
                            aria-label="Editar {{ e($n->nat_aten_descricao) }}"
                            @click="editando = true; aberto = true; preencherFormularioNaturezaAtendimento($el.dataset)"
                        >
                            <i class="bi bi-pencil" aria-hidden="true"></i>
                        </button>
                    </td>
                    <td>{{ $n->nat_aten_descricao }}</td>
                    <td>{{ optional($n->modeloRelatorio)->mod_rel_descricao }}</td>
                    <td>{{ optional($n->configModelo)->cfg_mod_nome }}</td>
                    <td>
                        <x-sbadmin::badge :type="$n->nat_aten_ativo ? 'success' : 'error'">
                            {{ $n->nat_aten_ativo ? 'Ativo' : 'Inativo' }}
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
                document.getElementById('nat_aten_config_modelo_id').value = data.configModelo || '';
                document.getElementById('nat_aten_ativo').checked = data.ativo === '1';

                document.getElementById('nat_aten_method').value = 'PUT';
                document.getElementById('form_natureza_atendimento').action = '{{ url('/naturezas-dos-atendimentos') }}/' + data.id;
            }

            function resetFormularioNaturezaAtendimento() {
                const form = document.getElementById('form_natureza_atendimento');
                form.reset();

                document.getElementById('nat_aten_id').value = '';
                document.getElementById('nat_aten_config_modelo_id').value = '';
                document.getElementById('nat_aten_ativo').checked = true;
                document.getElementById('nat_aten_method').value = 'POST';
                form.action = '{{ route('naturezas-dos-atendimentos.store') }}';
            }
        </script>
    @endpush
</x-layout>
