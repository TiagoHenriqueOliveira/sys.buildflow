{{-- OCORRÊNCIAS --}}
<div class="tab-pane fade" id="tab-ocorrencias">
    <div class="mb-3">
        <div class="form-row align-items-end">
            <div class="col-md-7">
                <label for="ocorrencia_label" class="font-weight-bold mb-1">Pesquisar</label>
                <input type="text"
                    id="ocorrencia_label"
                    class="form-control request"
                    placeholder="Digite ao menos 3 caracteres">
                <input type="hidden" id="ocorrencia_id" class="request">
            </div>

            <div class="col-md-3">
                <label for="ocorrencia_observacao" class="font-weight-bold mb-1">Observação</label>
                <input type="text"
                    id="ocorrencia_observacao"
                    class="form-control request"
                    maxlength="255"
                    placeholder="Opcional">
            </div>

            <div class="col-md-2">
                <button type="button" id="btnAddOcorrencia" class="btn btn-primary btn-icon-split">
                    <span class="icon text-white-50">
                        <i class="fas fa-plus"></i>
                    </span>
                    <span class="text">Adicionar</span>
                </button>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-striped" id="tableOcorrencias">
            <thead>
                <tr>
                    <th style="width:10%;">Ações</th>
                    <th>Ocorrência</th>
                    <th style="width:30%;">Observação</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>