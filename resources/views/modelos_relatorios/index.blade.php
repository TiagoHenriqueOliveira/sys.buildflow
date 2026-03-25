<x-layout title="Modelos de Relatórios">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <a href="javascript:void(0)"
                class="btn btn-info btn-icon-split"
                id="btnNovoModeloRelatorio">
                <span class="icon text-white-50">
                    <i class="fas fa-plus"></i>
                </span>
                <span class="text">Cadastrar</span>
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTableModelosRelatorios"
                    class="table table-translate dt-responsive"
                    data-url="{{ route('modelos-de-relatorios.index') }}"
                    width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Ações</th>
                            <th>Descrição</th>
                            <th>Tipo de Data</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @include('modelos_relatorios.modal')

    @push('scripts')
    <script src="{{ asset('js/app/modelos.relatorios.js') }}"></script>
    @endpush
</x-layout>