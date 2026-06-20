<x-layout title="Relatórios de Atendimento">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <a href="javascript:void(0)"
                class="btn btn-info btn-icon-split"
                id="btnNovoRelatorio">
                <span class="icon text-white-50">
                    <i class="fas fa-plus"></i>
                </span>
                <span class="text">Cadastrar</span>
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTableAtendimentosRelatorios"
                    class="table table-translate dt-responsive"
                    data-url="{{ route('atendimentos-relatorios.index') }}"
                    width="100%">
                    <thead>
                        <tr>
                            <th>Ações</th>
                            <th>Data</th>
                            <th>Cliente</th>
                            <th>Natureza</th>
                            <th>Técnico</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @include('atendimentos-relatorios.modal')

    @push('scripts')
    <script src="{{ asset('js/app/atendimentos.relatorios.js') }}"></script>
    @endpush
</x-layout>