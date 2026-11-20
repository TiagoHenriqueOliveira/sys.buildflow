<x-layout title="Clientes">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <a href="javascript:void(0)" class="btn btn-info btn-icon-split" id="btnNovoCliente">
                <span class="icon text-white-50">
                    <i class="fas fa-plus"></i>
                </span>
                <span class="text">Cadastrar</span>
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTableClientes"
                    class="table table-translate dt-responsive"
                    data-url="{{ route('clientes.index') }}"
                    width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Ações</th>
                            <th>Nome</th>
                            <th>Unidade</th>
                            <th>CNPJ</th>
                            <th>Cidade</th>
                            <th>UF</th>
                            <th>Telefone</th>
                            <th>E-mail</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @include('clientes.modal')

    @push('scripts')
    <script src="{{ asset('js/app/clientes.js') }}?v={{ filemtime(public_path('js/app/clientes.js')) }}"></script>
    @endpush
</x-layout>