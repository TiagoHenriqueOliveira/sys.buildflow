<x-layout title="Relatórios de Atendimento">
    <div
        x-data="{
            aberto: {{ $errors->any() ? 'true' : 'false' }},
        }"
    >
        <div class="sbadmin-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
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
            {{-- Filtros individuais por coluna — combinaveis entre si (AND:
                 cada filtro preenchido restringe ainda mais o resultado).
                 Substituem a busca unica que existia antes (removida a
                 pedido do cliente). --}}
            <div class="sbadmin-card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-2">
                        <label for="f_data" class="sbadmin-form-label">Data</label>
                        <input type="date" id="f_data" name="f_data" value="{{ $filtroData }}" class="form-control sbadmin-form-control">
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="f_cliente" class="sbadmin-form-label">Cliente</label>
                        <input type="text" id="f_cliente" name="f_cliente" value="{{ $filtroCliente }}" class="form-control sbadmin-form-control" placeholder="Cliente">
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="f_nr_proposta" class="sbadmin-form-label">Nº Proposta</label>
                        <input type="text" id="f_nr_proposta" name="f_nr_proposta" value="{{ $filtroNrProposta }}" class="form-control sbadmin-form-control" placeholder="Nº Proposta">
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="f_natureza" class="sbadmin-form-label">Natureza</label>
                        <input type="text" id="f_natureza" name="f_natureza" value="{{ $filtroNatureza }}" class="form-control sbadmin-form-control" placeholder="Natureza">
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="f_tecnico" class="sbadmin-form-label">Técnico</label>
                        <input type="text" id="f_tecnico" name="f_tecnico" value="{{ $filtroTecnico }}" class="form-control sbadmin-form-control" placeholder="Técnico">
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="f_status" class="sbadmin-form-label">Status</label>
                        <select id="f_status" name="f_status" class="form-select sbadmin-form-control">
                            <option value="">Todos</option>
                            @foreach(\App\Enums\AtendimentoRelatorioStatus::cases() as $statusOpcao)
                                <option value="{{ $statusOpcao->value }}" @selected($filtroStatus === (string) $statusOpcao->value)>{{ $statusOpcao->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-info">
                            <i class="bi bi-funnel" aria-hidden="true"></i> Aplicar Filtro
                        </button>
                        @if($temFiltro)
                            <a href="{{ route('atendimentos-relatorios.index') }}" class="btn btn-link">Limpar</a>
                        @endif
                    </div>
                </div>
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
