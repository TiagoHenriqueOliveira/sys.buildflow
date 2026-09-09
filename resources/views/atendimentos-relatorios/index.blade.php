<x-layout title="Relatórios de Atendimento">
    <div
        x-data="{
            aberto: {{ $errors->any() ? 'true' : 'false' }},
        }"
    >
        <div class="sbadmin-page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h2 class="sbadmin-page-heading">Relatórios de Atendimento</h2>
                <p class="sbadmin-page-subheading">Gerencie os relatórios de atendimento cadastrados no sistema.</p>
            </div>
            <button
                type="button"
                class="btn btn-primary sbadmin-btn-primary"
                @click="aberto = true"
            >
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Cadastrar
            </button>
        </div>

        @if(session('success'))
            <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
        @endif

        <form method="GET" action="{{ route('atendimentos-relatorios.index') }}" class="sbadmin-card mb-4">
            <div class="sbadmin-card-body d-flex flex-wrap gap-2 align-items-end">
                <div class="flex-grow-1" style="min-width: 240px;">
                    <label for="busca" class="sbadmin-form-label">Buscar</label>
                    <input
                        type="text"
                        id="busca"
                        name="busca"
                        value="{{ $busca }}"
                        class="form-control sbadmin-form-control"
                        placeholder="Cliente, natureza, técnico, proposta ou data"
                    >
                </div>
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="bi bi-search" aria-hidden="true"></i> Buscar
                </button>
                @if($busca !== '')
                    <a href="{{ route('atendimentos-relatorios.index') }}" class="btn btn-link">Limpar</a>
                @endif
            </div>
        </form>

        <x-sbadmin::table
            :headers="['Ações', 'Data', 'Cliente', 'Nº Proposta', 'Natureza', 'Técnico', 'Status']"
            :paginator="$relatorios"
            :count="$relatorios->count()"
            empty-message="Nenhum relatório encontrado."
        >
            @foreach($relatorios as $r)
                @php
                    $status = \App\Enums\AtendimentoRelatorioStatus::tryFrom($r->aten_rel_status);
                @endphp
                <tr>
                    <td class="text-center text-nowrap">
                        <a href="{{ route('atendimentos-relatorios.show', $r->aten_rel_id) }}" class="btn btn-sm btn-outline-secondary" title="Visualizar relatório">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </a>
                        <a href="{{ route('atendimentos-relatorios.pdf', $r->aten_rel_id) }}" class="btn btn-sm btn-outline-danger" title="Gerar PDF" target="_blank">
                            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                        </a>
                    </td>
                    <td>{{ optional($r->aten_rel_data)->format('d/m/Y') }}</td>
                    <td>{{ $r->atendimento?->cliente?->cli_nome ?? '-' }}</td>
                    <td>{{ $r->atendimento?->aten_nr_proposta ?? '' }}</td>
                    <td>{{ $r->atendimento?->natureza?->nat_aten_descricao ?? '-' }}</td>
                    <td>{{ $r->atendimento?->usuario?->user_nome ?? '-' }}</td>
                    <td>
                        @if($status)
                            <x-sbadmin::badge :type="match($status) {
                                \App\Enums\AtendimentoRelatorioStatus::Aprovado => 'success',
                                \App\Enums\AtendimentoRelatorioStatus::Revisar => 'warning',
                                default => 'info',
                            }">
                                {{ $status->label() }}
                            </x-sbadmin::badge>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-sbadmin::table>

        @include('atendimentos-relatorios.modal')
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                window.setupAutocomplete('#rel_aten_label', '#rel_aten_id', '{{ route('atendimentos_relatorios.autocomplete') }}', { minLength: 2 });
            });
        </script>
    @endpush
</x-layout>
