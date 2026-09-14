<div id="tab-dados" role="tabpanel" x-show="tab === 'dados'">
    <form id="form_relatorio_dados" data-action="{{ route('atendimentos-relatorios.update-dados', $atendimentoRelatorio->aten_rel_id) }}">
        @csrf
        {{-- LINHA: Data | Dia da Semana --}}
        <div class="row mb-3">
            <div class="col-md-3">
                <x-sbadmin::form.input
                    id="aten_rel_data"
                    type="date"
                    name="aten_rel_data"
                    label="Data"
                    :value="$atendimentoRelatorio->aten_rel_data->format('Y-m-d')"
                    max="{{ now()->format('Y-m-d') }}"
                />
            </div>

            <div class="col-md-3">
                <x-sbadmin::form.input
                    id="dia_semana_relatorio"
                    name="dia_semana_relatorio"
                    label="Dia da Semana"
                    :value="getFormatDiaSemana($atendimentoRelatorio->aten_rel_data)"
                    readonly
                />
            </div>
        </div>

        {{-- LINHA: Cliente | Contato | Responsável | Nº Proposta --}}
        <div class="row mb-3">
            <div class="col-md-4">
                <x-sbadmin::form.input
                    id="rel_dados_cliente"
                    name="rel_dados_cliente"
                    label="Cliente"
                    :value="$atendimentoRelatorio->atendimento->cliente->cli_nome"
                    readonly
                />
            </div>

            <div class="col-md-3">
                <x-sbadmin::form.input
                    id="rel_dados_contato"
                    name="rel_dados_contato"
                    label="Contato"
                    :value="$atendimentoRelatorio->atendimento->aten_contato"
                    readonly
                />
            </div>

            <div class="col-md-3">
                <x-sbadmin::form.input
                    id="rel_dados_responsavel"
                    name="rel_dados_responsavel"
                    label="Responsável"
                    :value="$atendimentoRelatorio->atendimento->aten_responsavel"
                    readonly
                />
            </div>

            <div class="col-md-2">
                <x-sbadmin::form.input
                    id="rel_dados_proposta"
                    name="rel_dados_proposta"
                    label="Nº Proposta"
                    :value="$atendimentoRelatorio->atendimento->aten_nr_proposta"
                    readonly
                />
            </div>
        </div>

        {{-- LINHA: Endereço | Cidade | UF --}}
        <div class="row mb-3">
            <div class="col-md-6">
                <x-sbadmin::form.input
                    id="rel_dados_endereco"
                    name="rel_dados_endereco"
                    label="Endereço"
                    :value="$atendimentoRelatorio->atendimento->aten_endereco"
                    readonly
                />
            </div>
            <div class="col-md-4">
                <x-sbadmin::form.input
                    id="rel_dados_cidade"
                    name="rel_dados_cidade"
                    label="Cidade"
                    :value="$atendimentoRelatorio->atendimento->cliente->cli_cidade"
                    readonly
                />
            </div>
            <div class="col-md-2">
                <x-sbadmin::form.input
                    id="rel_dados_uf"
                    name="rel_dados_uf"
                    label="UF"
                    :value="$atendimentoRelatorio->atendimento->cliente->cli_uf"
                    readonly
                />
            </div>
        </div>
    </form>

    {{-- Equipamentos do atendimento — mesmo padrão visual da aba Equipamentos
         do cadastro de Atendimento (tabela sempre somente leitura aqui, já
         que pertence ao atendimento, não ao relatório). Pedido do cliente
         (2026-09-14): tirado o ícone antes do label "Equipamentos" (não
         renderizava, ficando um espaço vazio antes do texto) — o cadastro de
         Atendimento (referência) também não usa ícone aqui, então manter
         h6 simples evita reintroduzir o mesmo problema. --}}
    @php $equipamentos = $atendimentoRelatorio->atendimento->equipamentos; @endphp
    @if($equipamentos->isNotEmpty())
    <hr class="my-3">
    <h6 class="fw-bold mb-2">Equipamentos</h6>
    <div class="table-responsive">
        <table class="table table-sm table-striped table-hover mb-0">
            <thead>
                <tr>
                    <th class="align-middle">Descrição</th>
                </tr>
            </thead>
            <tbody>
                @foreach($equipamentos as $eq)
                <tr>
                    <td class="align-middle">{{ $eq->aten_equip_descricao }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Anexos do atendimento --}}
    @php $anexosAten = $atendimentoRelatorio->atendimento->anexos; @endphp
    @if($anexosAten->isNotEmpty())
    <hr class="my-3">
    <h6 class="fw-bold mb-2">Anexos do Atendimento</h6>
    <ul class="list-unstyled mb-0">
        @foreach($anexosAten as $anx)
        <li class="mb-1">
            <a href="{{ asset('midia/' . $anx->aten_anexo_path) }}" target="_blank" class="text-primary">
                <i class="bi bi-file-earmark me-1" aria-hidden="true"></i>{{ $anx->aten_anexo_nome_original }}
            </a>
        </li>
        @endforeach
    </ul>
    @endif
</div>