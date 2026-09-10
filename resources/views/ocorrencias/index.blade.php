<x-layout title="Ocorrências">
    <div
        x-data="{
            aberto: {{ $errors->any() ? 'true' : 'false' }},
            editando: {{ old('ocor_id') ? 'true' : 'false' }},
        }"
    >
        <div class="sbadmin-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="sbadmin-page-heading">Ocorrências</h2>
                <p class="sbadmin-page-subheading">Gerencie as ocorrências cadastradas no sistema.</p>
            </div>
            <button
                type="button"
                class="btn btn-primary sbadmin-btn-primary"
                @click="editando = false; aberto = true; resetFormularioOcorrencia()"
            >
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Cadastrar
            </button>
        </div>

        @if(session('success'))
            <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
        @endif

        <form method="GET" action="{{ route('ocorrencias.index') }}" class="sbadmin-card mb-4">
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
            :headers="['Ações', 'Descrição', 'Status']"
            :paginator="$ocorrencias"
            :count="$ocorrencias->count()"
            empty-message="Nenhuma ocorrência encontrada."
        >
            @foreach($ocorrencias as $o)
                <tr>
                    <td class="text-center">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            data-id="{{ $o->ocor_id }}"
                            data-descricao="{{ e($o->ocor_descricao) }}"
                            data-ativo="{{ (int) $o->ocor_ativo }}"
                            aria-label="Editar {{ e($o->ocor_descricao) }}"
                            @click="editando = true; aberto = true; preencherFormularioOcorrencia($el.dataset)"
                        >
                            <i class="bi bi-pencil" aria-hidden="true"></i>
                        </button>
                    </td>
                    <td>{{ $o->ocor_descricao }}</td>
                    <td>
                        <x-sbadmin::badge :type="$o->ocor_ativo ? 'success' : 'error'">
                            {{ $o->ocor_ativo ? 'Ativo' : 'Desativado' }}
                        </x-sbadmin::badge>
                    </td>
                </tr>
            @endforeach
        </x-sbadmin::table>

        @include('ocorrencias.modal')
    </div>

    @push('scripts')
        <script>
            function preencherFormularioOcorrencia(data) {
                document.getElementById('ocor_id').value = data.id || '';
                document.getElementById('ocor_descricao').value = data.descricao || '';
                document.getElementById('ocor_ativo').checked = data.ativo === '1';

                document.getElementById('ocor_method').value = 'PUT';
                document.getElementById('form_ocorrencia').action = '{{ url('/ocorrencias') }}/' + data.id;
            }

            function resetFormularioOcorrencia() {
                const form = document.getElementById('form_ocorrencia');
                form.reset();

                document.getElementById('ocor_id').value = '';
                document.getElementById('ocor_ativo').checked = true;
                document.getElementById('ocor_method').value = 'POST';
                form.action = '{{ route('ocorrencias.store') }}';
            }
        </script>
    @endpush
</x-layout>
