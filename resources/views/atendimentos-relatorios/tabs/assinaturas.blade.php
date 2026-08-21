{{-- ASSINATURA --}}
<div class="tab-pane fade" id="tab-assinatura">
    <form id="form_relatorio_assinaturas" data-action="{{ route('atendimentos-relatorios.update-assinaturas', $atendimentoRelatorio->aten_rel_id) }}">
        @csrf

        <div class="form-group">
            <label class="font-weight-bold">Status do Relatório</label>
            @php $statusClasses = [0 => 'info', 1 => 'warning', 2 => 'success']; @endphp
            <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                @foreach([0 => 'Preenchendo', 1 => 'Revisar', 2 => 'Aprovado'] as $value => $label)
                    <label class="btn btn-outline-{{ $statusClasses[$value] }} {{ $atendimentoRelatorio->aten_rel_status === $value ? 'active' : '' }}">
                        <input type="radio" name="aten_rel_status" value="{{ $value }}" autocomplete="off" {{ $atendimentoRelatorio->aten_rel_status === $value ? 'checked' : '' }}> {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-header font-weight-bold">Assinatura Técnico</div>
                    <div class="card-body">
                        <canvas id="assinaturaResponsavelCanvas" class="border rounded w-100" width="600" height="220"></canvas>
                        <div class="mt-2 d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm btn-clear-signature" data-signature="responsavel">Limpar</button>
                            <button type="button" class="btn btn-primary btn-sm btn-save-signature" data-signature="responsavel">Salvar Assinatura</button>
                        </div>
                        {{-- RF006: nome (obrigatório) e CPF (opcional) de quem assinou, em vez de assumir o responsável cadastrado no atendimento --}}
                        <div class="form-row mt-3">
                            <div class="col-md-8">
                                <label class="font-weight-bold mb-1" for="assinatura_responsavel_nome">Nome de quem assinou</label>
                                <input type="text" class="form-control form-control-sm" id="assinatura_responsavel_nome" maxlength="100"
                                    placeholder="Nome completo" value="{{ optional($atendimentoRelatorio->assinaturaResponsavel())->aten_rel_ass_nome }}">
                            </div>
                            <div class="col-md-4">
                                <label class="font-weight-bold mb-1" for="assinatura_responsavel_cpf">CPF (opcional)</label>
                                <input type="text" class="form-control form-control-sm" id="assinatura_responsavel_cpf" maxlength="14"
                                    placeholder="000.000.000-00" value="{{ optional($atendimentoRelatorio->assinaturaResponsavel())->aten_rel_ass_cpf }}">
                            </div>
                        </div>
                        <div class="mt-3">
                            <p class="mb-1 font-weight-bold">Preview atual</p>
                            <div id="assinaturaResponsavelPreview">
                                @if(optional($atendimentoRelatorio->assinaturaResponsavel())->aten_rel_ass_path)
                                    <img src="{{ asset('midia/' . $atendimentoRelatorio->assinaturaResponsavel()->aten_rel_ass_path) }}" class="img-fluid border" alt="Assinatura Técnico">
                                @else
                                    <span class="text-muted">Nenhuma assinatura registrada.</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-header font-weight-bold">Assinatura Cliente</div>
                    <div class="card-body">
                        <canvas id="assinaturaClienteCanvas" class="border rounded w-100" width="600" height="220"></canvas>
                        <div class="mt-2 d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm btn-clear-signature" data-signature="cliente">Limpar</button>
                            <button type="button" class="btn btn-success btn-sm btn-save-signature" data-signature="cliente">Salvar Assinatura</button>
                        </div>
                        {{-- RF006: nome (obrigatório) e CPF (opcional) de quem assinou, em vez de assumir o responsável cadastrado no atendimento --}}
                        <div class="form-row mt-3">
                            <div class="col-md-8">
                                <label class="font-weight-bold mb-1" for="assinatura_cliente_nome">Nome de quem assinou</label>
                                <input type="text" class="form-control form-control-sm" id="assinatura_cliente_nome" maxlength="100"
                                    placeholder="Nome completo" value="{{ optional($atendimentoRelatorio->assinaturaCliente())->aten_rel_ass_nome }}">
                            </div>
                            <div class="col-md-4">
                                <label class="font-weight-bold mb-1" for="assinatura_cliente_cpf">CPF (opcional)</label>
                                <input type="text" class="form-control form-control-sm" id="assinatura_cliente_cpf" maxlength="14"
                                    placeholder="000.000.000-00" value="{{ optional($atendimentoRelatorio->assinaturaCliente())->aten_rel_ass_cpf }}">
                            </div>
                        </div>
                        <div class="mt-3">
                            <p class="mb-1 font-weight-bold">Preview atual</p>
                            <div id="assinaturaClientePreview">
                                @if(optional($atendimentoRelatorio->assinaturaCliente())->aten_rel_ass_path)
                                    <img src="{{ asset('midia/' . $atendimentoRelatorio->assinaturaCliente()->aten_rel_ass_path) }}" class="img-fluid border" alt="Assinatura Cliente">
                                @else
                                    <span class="text-muted">Nenhuma assinatura registrada.</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>