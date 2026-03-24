<x-layout title="Usuários">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <a href="javascript:void(0)"
                class="btn btn-info btn-icon-split"
                id="btnNovoUsuario">
                <span class="icon text-white-50">
                    <i class="fas fa-plus"></i>
                </span>
                <span class="text">Cadastrar</span>
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTableUsuarios"
                    class="table table-translate dt-responsive"
                    data-url="{{ route('usuarios.index') }}"
                    width="100%"
                    cellspacing="0">
                    <thead>
                        <tr>
                            <th>Ações</th>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Nível</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @include('usuarios.modal')

    @push('scripts')
    <script src="{{ asset('js/app/usuarios.js') }}"></script>
    @endpush
</x-layout>