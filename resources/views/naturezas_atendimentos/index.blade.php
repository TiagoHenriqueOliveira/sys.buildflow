<x-layout title="Naturezas de Atendimento">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <a href="javascript:void(0)" class="btn btn-info btn-icon-split" id="btnNovaNaturezaAtendimento">
                <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
                <span class="text">Cadastrar</span>
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTableNaturezasAtendimentos"
                    class="table table-translate dt-responsive"
                    data-url="{{ route('naturezas-dos-atendimentos.index') }}"
                    width="100%">
                    <thead>
                        <tr>
                            <th>Ações</th>
                            <th>Descrição</th>
                            <th>Setor</th>
                            <th>Modelo de Relatório</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @include('naturezas_atendimentos.modal', ['modelosRelatorios' => $modelosRelatorios, 'tiposAtivos' => $tiposAtivos])

    @push('scripts')
    <script src="{{ asset('js/app/naturezas.atendimentos.js') }}"></script>
    @endpush
</x-layout>