<x-layout title="Visualizar Relatório">
    <div class="card shadow mb-4">
        {{-- HEADER --}}
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <p class="h5 font-weight-bold text-primary text-decoration-underline mb-0">
                {{ $atendimentoRelatorio->modeloRelatorio->mod_rel_descricao }}
            </p>
        </div>

        <div class="card-body ui-front">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#tab-dados">Dados</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-horarios">Horário</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-clima">Clima</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-descricao">Descrição</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-servicos">Serviços Prestados</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-pecas">Peças Substituídas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-ocorrencias">Ocorrências</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-info-adicionais">Inf. Adicionais</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-anexos">Anexos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-assinatura">Assinatura</a>
                </li>
            </ul>

            <div class="tab-content p-2">
                @include('atendimentos-relatorios.tabs.dados')
                @include('atendimentos-relatorios.tabs.horarios')
                @include('atendimentos-relatorios.tabs.clima')
                @include('atendimentos-relatorios.tabs.descricao')
                @include('atendimentos-relatorios.tabs.servicos-prestados')
                @include('atendimentos-relatorios.tabs.pecas-substituidas')
                @include('atendimentos-relatorios.tabs.ocorrencias')
                @include('atendimentos-relatorios.tabs.informacoes-adicionais')
                @include('atendimentos-relatorios.tabs.anexos')
                @include('atendimentos-relatorios.tabs.assinaturas')
            </div>

        </div>

        {{-- FOOTER DA PÃGINA --}}
        <x-page-footer :showSave="true" saveText="Atualizar" :relatorioId="$atendimentoRelatorio->aten_rel_id" :backRoute="route('atendimentos-relatorios.index')" />
    </div>

    @push('scripts')
    <script src="{{ asset('js/app/atendimentos.relatorios.js') }}?v={{ filemtime(public_path('js/app/atendimentos.relatorios.js')) }}"></script>
    @endpush
</x-layout>
