<x-layout title="Tipos de Atendimentos">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <a href="javascript:void(0)"
                class="btn btn-info btn-icon-split"
                id="btnNovoTipoAtendimento">
                <span class="icon text-white-50">
                    <i class="fas fa-plus"></i>
                </span>
                <span class="text">Cadastrar</span>
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTableTiposAtendimentos"
                    class="table table-translate dt-responsive"
                    data-url="{{ route('tipos-de-atendimentos.index') }}"
                    width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Ações</th>
                            <th>Natureza</th>
                            <th>Descrição</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @include('tipos_atendimentos.modal', ['naturezasAtivas' => $naturezasAtivas])

    @push('scripts')
    <script src="{{ asset('js/app/tipos.atendimentos.js') }}"></script>
    @endpush
</x-layout>