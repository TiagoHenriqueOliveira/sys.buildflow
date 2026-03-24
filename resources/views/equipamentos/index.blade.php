<x-layout title="Equipamentos">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <a href="javascript:void(0)"
                class="btn btn-info btn-icon-split"
                id="btnNovoEquipamento">
                <span class="icon text-white-50">
                    <i class="fas fa-plus"></i>
                </span>
                <span class="text">Cadastrar</span>
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTableEquipamentos"
                    class="table table-translate dt-responsive"
                    data-url="{{ route('equipamentos.index') }}"
                    width="100%">
                    <thead>
                        <tr>
                            <th>Ações</th>
                            <th>Descrição</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @include('equipamentos.modal')

    @push('scripts')
    <script src="{{ asset('js/app/equipamentos.js') }}"></script>
    @endpush
</x-layout>