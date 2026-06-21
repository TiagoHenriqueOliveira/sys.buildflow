{{-- SERVIÇOS PRESTADOS --}}
<div class="tab-pane fade" id="tab-servicos" role="tabpanel">
    <div class="mb-3">
        <div class="form-row align-items-end">
            <div class="col-md-6">
                <label for="servico_descricao" class="font-weight-bold mb-1">Descrição do Serviço</label>
                <input type="text"
                    id="servico_descricao"
                    class="form-control"
                    maxlength="255"
                    placeholder="Descreva o serviço realizado">
            </div>

            <div class="col-md-2">
                <button type="button" id="btnAddServico" class="btn btn-primary btn-icon-split">
                    <span class="icon text-white-50">
                        <i class="fas fa-plus"></i>
                    </span>
                    <span class="text">Adicionar</span>
                </button>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-striped" id="tableServicos">
            <thead>
                <tr>
                    <th style="width:10%;">Ações</th>
                    <th>Serviço</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <input type="hidden" id="servicos_data" value="{{ e($atendimentoRelatorio->aten_rel_servicos_prestados ?? '') }}">
</div>
