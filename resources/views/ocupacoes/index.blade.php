<x-layout title="Mão de Obra">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <a href="javascript:void(0)"
                class="btn btn-info btn-icon-split"
                id="btnNovaMaoDeObra">
                <span class="icon text-white-50">
                    <i class="fas fa-plus"></i>
                </span>
                <span class="text">Cadastrar</span>
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTableMaoDeObra"
                    class="table table-translate dt-responsive"
                    data-url="{{ route('mao-de-obra.index') }}"
                    width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Ações</th>
                            <th>Tipo</th>
                            <th>Descrição</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @include('ocupacoes.modal', ['tiposOcupacoesAtivos' => $tiposOcupacoesAtivos])

    @push('scripts')
    <script src="{{ asset('js/app/mao.de.obra.js') }}"></script>
    @endpush
</x-layout>