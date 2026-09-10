<x-layout title="CRM | Tipos de Orçamento">
    <div
        x-data="{
            aberto: {{ $errors->any() ? 'true' : 'false' }},
            editando: {{ old('crm_tp_orc_id') ? 'true' : 'false' }},
        }"
    >
        <div class="sbadmin-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="sbadmin-page-heading">CRM | Tipos de Orçamento</h2>
                <p class="sbadmin-page-subheading">Vincule cada tipo de sistema de orçamento a um modelo do Configurador (setor Comercial).</p>
            </div>
            <button
                type="button"
                class="btn btn-primary sbadmin-btn-primary"
                @click="editando = false; aberto = true; resetFormularioTipoOrcamento()"
            >
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Cadastrar
            </button>
        </div>

        @if(session('success'))
            <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
        @endif

        <form method="GET" action="{{ route('crm.tipos-orcamento.index') }}" class="sbadmin-card mb-4">
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
            :headers="['Ações', 'Nome', 'Modelo (Configurador)', 'Status']"
            :paginator="$tipos"
            :count="$tipos->count()"
            empty-message="Nenhum tipo de orçamento cadastrado."
        >
            @foreach($tipos as $t)
                <tr class="{{ $t->crm_tp_orc_ativo ? '' : 'table-danger' }}">
                    <td class="text-center">
                        <button
                            type="button"
                            class="btn btn-sm sbadmin-table-action-btn"
                            data-id="{{ $t->crm_tp_orc_id }}"
                            data-nome="{{ e($t->crm_tp_orc_nome) }}"
                            data-config-modelo="{{ (int) $t->crm_tp_orc_config_modelo_id }}"
                            data-ativo="{{ (int) $t->crm_tp_orc_ativo }}"
                            aria-label="Editar {{ e($t->crm_tp_orc_nome) }}"
                            @click="editando = true; aberto = true; preencherFormularioTipoOrcamento($el.dataset)"
                        >
                            <i class="bi bi-pencil" aria-hidden="true"></i>
                        </button>
                    </td>
                    <td>{{ $t->crm_tp_orc_nome }}</td>
                    <td>{{ optional($t->configModelo)->cfg_mod_nome }}</td>
                    <td>
                        <x-sbadmin::badge :type="$t->crm_tp_orc_ativo ? 'success' : 'error'">
                            {{ $t->crm_tp_orc_ativo ? 'Ativo' : 'Inativo' }}
                        </x-sbadmin::badge>
                    </td>
                </tr>
            @endforeach
        </x-sbadmin::table>

        @include('crm.tipos-orcamento.modal')
    </div>

    @push('scripts')
        <script>
            function preencherFormularioTipoOrcamento(data) {
                document.getElementById('crm_tp_orc_id').value = data.id || '';
                document.getElementById('crm_tp_orc_nome').value = data.nome || '';
                document.getElementById('crm_tp_orc_config_modelo_id').value = data.configModelo || '';
                document.getElementById('crm_tp_orc_ativo').checked = data.ativo === '1';

                document.getElementById('crm_tp_orc_method').value = 'PUT';
                document.getElementById('form_tipo_orcamento').action = '{{ url('/crm/tipos-orcamento') }}/' + data.id;
            }

            function resetFormularioTipoOrcamento() {
                const form = document.getElementById('form_tipo_orcamento');
                form.reset();

                document.getElementById('crm_tp_orc_id').value = '';
                document.getElementById('crm_tp_orc_ativo').checked = true;
                document.getElementById('crm_tp_orc_method').value = 'POST';
                form.action = '{{ route('crm.tipos-orcamento.store') }}';
            }
        </script>
    @endpush
</x-layout>