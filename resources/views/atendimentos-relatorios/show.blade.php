<x-layout title="Visualizar Relatório">
    <div class="card shadow mb-4">
        {{-- HEADER --}}
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <p class="h5 font-weight-bold text-primary text-decoration-underline mb-0">
                {{ $atendimentoRelatorio->modeloRelatorio->mod_rel_descricao }}
            </p>
        </div>

        <div class="card-body ui-front">
            {{-- ABAS --}}
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
                    <a class="nav-link" data-toggle="tab" href="#tab-mao-obra">Mão de Obra</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-equipamentos">Equipamentos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-atividades">Atividades</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-ocorrencias">Ocorrências</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-checklist">Checklist</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-materiais">Materiais</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-comentarios">Comentários</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-anexos">Anexos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-fotos">Fotos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-videos">Vídeos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-assinatura">Assinatura</a>
                </li>
            </ul>

            <div class="tab-content p-2">
                @include('atendimentos-relatorios.tabs.dados')
                @include('atendimentos-relatorios.tabs.horarios')
                @include('atendimentos-relatorios.tabs.clima')
                @include('atendimentos-relatorios.tabs.mao-obra')
                @include('atendimentos-relatorios.tabs.equipamentos')
                @include('atendimentos-relatorios.tabs.atividades')
                @include('atendimentos-relatorios.tabs.ocorrencias')
                @include('atendimentos-relatorios.tabs.checklist')
                @include('atendimentos-relatorios.tabs.materiais')
                @include('atendimentos-relatorios.tabs.comentarios')
                @include('atendimentos-relatorios.tabs.anexos')
                @include('atendimentos-relatorios.tabs.assinaturas')
            </div>

        </div>

        {{-- FOOTER DA PÁGINA --}}
        <x-page-footer :showSave="true" saveText="Atualizar" :relatorioId="$atendimentoRelatorio->aten_rel_id" :backRoute="route('atendimentos-relatorios.index')" />
    </div>

    @push('scripts')
    <script src="{{ asset('js/app/atendimentos.relatorios.js') }}"></script>
    @endpush
</x-layout>