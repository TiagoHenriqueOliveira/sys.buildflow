{{-- ASSINATURA --}}
<div id="tab-assinatura" role="tabpanel" x-show="tab === 'assinatura'">
    <form id="form_relatorio_assinaturas" data-action="{{ route('atendimentos-relatorios.update-assinaturas', $atendimentoRelatorio->aten_rel_id) }}">
        @csrf

        <div class="form-group">
            <label class="fw-bold">Status do Relatório</label>
            @php $statusClasses = [0 => 'info', 1 => 'warning', 2 => 'success']; @endphp
            {{-- Grupo de radios "toggle" — btn-group-toggle/data-toggle="buttons" era
                 API JS do Bootstrap4; convertido pro padrão .btn-check do Bootstrap5
                 (puramente CSS via :checked + label, sem depender de nenhum JS do
                 Bootstrap, que este stack não carrega — só Alpine). --}}
            <div class="btn-group d-flex" role="group">
                @foreach([0 => 'Preenchendo', 1 => 'Revisar', 2 => 'Aprovado'] as $value => $label)
                    <input type="radio" class="btn-check" name="aten_rel_status" id="aten_rel_status_{{ $value }}" value="{{ $value }}" autocomplete="off" {{ $atendimentoRelatorio->aten_rel_status === $value ? 'checked' : '' }}>
                    <label class="btn btn-outline-{{ $statusClasses[$value] }}" for="aten_rel_status_{{ $value }}">{{ $label }}</label>
                @endforeach
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-header fw-bold">Assinatura Técnico</div>
                    <div class="card-body">
                        <canvas id="assinaturaResponsavelCanvas" class="border rounded w-100" width="600" height="220"></canvas>
                        <div class="mt-2 d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm btn-clear-signature" data-signature="responsavel">Limpar</button>
                            <button type="button" class="btn btn-primary btn-sm btn-save-signature" data-signature="responsavel">Salvar Assinatura</button>
                        </div>
                        {{-- Nome/CPF de quem assinou são exigidos só do cliente — o técnico já
                             é o usuário logado, identidade conhecida. --}}
                        <div class="mt-3">
                            <p class="mb-1 fw-bold">Preview atual</p>
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
                    <div class="card-header fw-bold">Assinatura Cliente</div>
                    <div class="card-body">
                        <canvas id="assinaturaClienteCanvas" class="border rounded w-100" width="600" height="220"></canvas>
                        <div class="mt-2 d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm btn-clear-signature" data-signature="cliente">Limpar</button>
                            <button type="button" class="btn btn-success btn-sm btn-save-signature" data-signature="cliente">Salvar Assinatura</button>
                        </div>
                        {{-- Nome e CPF de quem assinou são obrigatórios para o cliente. --}}
                        <div class="row g-2 mt-3">
                            <div class="col-md-8">
                                <label class="fw-bold mb-1" for="assinatura_cliente_nome">Nome de quem assinou <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="assinatura_cliente_nome" maxlength="100"
                                    placeholder="Nome completo" value="{{ optional($atendimentoRelatorio->assinaturaCliente())->aten_rel_ass_nome }}">
                            </div>
                            <div class="col-md-4">
                                <label class="fw-bold mb-1" for="assinatura_cliente_cpf">CPF <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="assinatura_cliente_cpf" maxlength="14"
                                    placeholder="000.000.000-00" value="{{ optional($atendimentoRelatorio->assinaturaCliente())->aten_rel_ass_cpf }}">
                            </div>
                        </div>
                        <div class="mt-3">
                            <p class="mb-1 fw-bold">Preview atual</p>
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

        {{-- BF10 - observação do supervisor no momento da aprovação/revisão. --}}
        <div class="form-group mt-2">
            <label class="fw-bold" for="aten_rel_observacao_supervisor">Observação do Supervisor</label>
            <textarea class="form-control" id="aten_rel_observacao_supervisor" rows="3"
                placeholder="Observações sobre a aprovação/revisão deste relatório...">{{ $atendimentoRelatorio->aten_rel_observacao_supervisor }}</textarea>
        </div>
    </form>
</div>
