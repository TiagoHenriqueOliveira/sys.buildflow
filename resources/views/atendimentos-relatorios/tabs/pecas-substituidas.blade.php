{{-- PEÇAS SUBSTITUÍDAS --}}
<div class="tab-pane fade" id="tab-pecas" role="tabpanel">
    <div class="mb-3">
        <div class="form-row align-items-end">
            <div class="col-md-6">
                <label for="peca_descricao" class="font-weight-bold mb-1">Descrição da Peça</label>
                <input type="text"
                    id="peca_descricao"
                    class="form-control"
                    maxlength="255"
                    placeholder="Descreva a peça substituída">
            </div>

            <div class="col-md-2">
                <button type="button" id="btnAddPeca" class="btn btn-primary btn-icon-split">
                    <span class="icon text-white-50">
                        <i class="fas fa-plus"></i>
                    </span>
                    <span class="text">Adicionar</span>
                </button>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-striped" id="tablePecas">
            <thead>
                <tr>
                    <th style="width:10%;">Ações</th>
                    <th>Peça</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <input type="hidden" id="pecas_data" value="{{ e($atendimentoRelatorio->aten_rel_pecas_substituidas ?? '') }}">
</div>
