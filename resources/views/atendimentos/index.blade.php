<x-layout title="Atendimentos">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            @if(auth()->user()->user_nivel_acesso === 0)
            <a href="javascript:void(0)"
                class="btn btn-info btn-icon-split"
                id="btnNovoAtendimento">
                <span class="icon text-white-50">
                    <i class="fas fa-plus"></i>
                </span>
                <span class="text">Cadastrar</span>
            </a>
            @endif
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTableAtendimentos"
                    class="table table-translate dt-responsive"
                    data-url="{{ route('atendimentos.index') }}"
                    width="100%">
                    <thead>
                        <tr>
                            <th>AÃ§Ãµes</th>
                            <th>Natureza</th>
                            <th>TÃ©cnico</th>
                            <th>Cliente</th>
                            <th>PerÃ­odo</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    @include('atendimentos.modal')

    @push('scripts')
    <script src="{{ asset('js/app/atendimentos.js') }}?v={{ filemtime(public_path('js/app/atendimentos.js')) }}"></script>
    @endpush
</x-layout>
