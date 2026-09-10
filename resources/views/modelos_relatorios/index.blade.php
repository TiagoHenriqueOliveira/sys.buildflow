<x-layout title="Modelos de Relatórios">
    <div
        x-data="{
            aberto: {{ $errors->any() ? 'true' : 'false' }},
            editando: {{ old('mod_rel_id') ? 'true' : 'false' }},
        }"
    >
        <div class="sbadmin-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="sbadmin-page-heading">Modelos de Relatórios</h2>
                <p class="sbadmin-page-subheading">Gerencie os modelos de relatórios cadastrados no sistema.</p>
            </div>
            <button
                type="button"
                class="btn btn-primary sbadmin-btn-primary"
                @click="editando = false; aberto = true; resetFormularioModeloRelatorio()"
            >
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Cadastrar
            </button>
        </div>

        @if(session('success'))
            <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
        @endif

        <form method="GET" action="{{ route('modelos-de-relatorios.index') }}" class="sbadmin-card mb-4">
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
            :headers="['Ações', 'Descrição', 'Tipo de Data', 'Status']"
            :paginator="$modelos"
            :count="$modelos->count()"
            empty-message="Nenhum modelo de relatório encontrado."
        >
            @foreach($modelos as $m)
                <tr>
                    <td class="text-center">
                        <button
                            type="button"
                            class="btn btn-sm sbadmin-table-action-btn"
                            data-id="{{ $m->mod_rel_id }}"
                            data-descricao="{{ e($m->mod_rel_descricao) }}"
                            data-tp-data="{{ (int) $m->mod_rel_tp_data }}"
                            data-ativo="{{ (int) $m->mod_rel_ativo }}"
                            aria-label="Editar {{ e($m->mod_rel_descricao) }}"
                            @click="editando = true; aberto = true; preencherFormularioModeloRelatorio($el.dataset)"
                        >
                            <i class="bi bi-pencil" aria-hidden="true"></i>
                        </button>
                    </td>
                    <td>{{ $m->mod_rel_descricao }}</td>
                    <td>{{ (int) $m->mod_rel_tp_data === 0 ? 'Relatório Diário' : 'Relatório Período' }}</td>
                    <td>
                        <x-sbadmin::badge :type="$m->mod_rel_ativo ? 'success' : 'error'">
                            {{ $m->mod_rel_ativo ? 'Ativo' : 'Desativado' }}
                        </x-sbadmin::badge>
                    </td>
                </tr>
            @endforeach
        </x-sbadmin::table>

        @include('modelos_relatorios.modal')
    </div>

    @push('scripts')
        <script>
            function preencherFormularioModeloRelatorio(data) {
                document.getElementById('mod_rel_id').value = data.id || '';
                document.getElementById('mod_rel_descricao').value = data.descricao || '';
                document.getElementById('mod_rel_tp_data').value = data.tpData || '0';
                document.getElementById('mod_rel_ativo').checked = data.ativo === '1';

                document.getElementById('mod_rel_method').value = 'PUT';
                document.getElementById('form_modelo_relatorio').action = '{{ url('/modelos-de-relatorios') }}/' + data.id;
            }

            function resetFormularioModeloRelatorio() {
                const form = document.getElementById('form_modelo_relatorio');
                form.reset();

                document.getElementById('mod_rel_id').value = '';
                document.getElementById('mod_rel_tp_data').value = '0';
                document.getElementById('mod_rel_ativo').checked = true;
                document.getElementById('mod_rel_method').value = 'POST';
                form.action = '{{ route('modelos-de-relatorios.store') }}';
            }
        </script>
    @endpush
</x-layout>
