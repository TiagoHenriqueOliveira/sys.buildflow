<x-layout title="Segmentos">
    <div
        x-data="{
            aberto: {{ $errors->any() ? 'true' : 'false' }},
            editando: {{ old('seg_id') ? 'true' : 'false' }},
        }"
    >
        <div class="sbadmin-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="sbadmin-page-heading">Segmentos</h2>
                <p class="sbadmin-page-subheading">Opções de segmento exibidas no cadastro de cliente (aba Dados Gerais).</p>
            </div>
            <button
                type="button"
                class="btn btn-primary sbadmin-btn-primary"
                @click="editando = false; aberto = true; resetFormularioSegmento()"
            >
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Cadastrar
            </button>
        </div>

        @if(session('success'))
            <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
        @endif

        <form method="GET" action="{{ route('segmentos.index') }}" class="sbadmin-card mb-4">
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
            :paginator="$segmentos"
            :count="$segmentos->count()"
            empty-message="Nenhum segmento cadastrado."
        >
            @foreach($segmentos as $s)
                <tr>
                    <td class="text-center">
                        <button
                            type="button"
                            class="btn btn-sm sbadmin-table-action-btn"
                            data-id="{{ $s->seg_id }}"
                            data-descricao="{{ e($s->seg_descricao) }}"
                            data-ativo="{{ (int) $s->seg_ativo }}"
                            aria-label="Editar {{ e($s->seg_descricao) }}"
                            @click="editando = true; aberto = true; preencherFormularioSegmento($el.dataset)"
                        >
                            <i class="bi bi-pencil" aria-hidden="true"></i>
                        </button>
                    </td>
                    <td>{{ $s->seg_descricao }}</td>
                    <td>
                        <x-sbadmin::badge :type="$s->seg_ativo ? 'success' : 'error'">
                            {{ $s->seg_ativo ? 'Ativo' : 'Inativo' }}
                        </x-sbadmin::badge>
                    </td>
                </tr>
            @endforeach
        </x-sbadmin::table>

        @include('segmentos.modal')
    </div>

    @push('scripts')
        <script>
            function preencherFormularioSegmento(data) {
                document.getElementById('seg_id').value = data.id || '';
                document.getElementById('seg_descricao').value = data.descricao || '';
                document.getElementById('seg_ativo').checked = data.ativo === '1';

                document.getElementById('seg_method').value = 'PUT';
                document.getElementById('form_segmento').action = '{{ url('/segmentos') }}/' + data.id;
            }

            function resetFormularioSegmento() {
                const form = document.getElementById('form_segmento');
                form.reset();

                document.getElementById('seg_id').value = '';
                document.getElementById('seg_ativo').checked = true;
                document.getElementById('seg_method').value = 'POST';
                form.action = '{{ route('segmentos.store') }}';
            }
        </script>
    @endpush
</x-layout>