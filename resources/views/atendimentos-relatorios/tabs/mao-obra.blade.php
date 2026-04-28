{{-- MÃO DE OBRA --}}
<div class="tab-pane fade" id="tab-mao-obra">
    <div class="mb-3">
        <div class="form-row align-items-end">
            <div class="col-md-7">
                <label for="mao_obra_label" class="font-weight-bold mb-1">Pesquisar</label>
                <input type="text" id="mao_obra_label" class="form-control request" placeholder="Digite ao menos 3 caracteres">
                <input type="hidden" id="mao_obra_id" class="request">
                <input type="hidden" id="mao_obra_tp_id" class="request">
            </div>

            <div class="col-md-2">
                <label for="mao_obra_qtd" class="font-weight-bold mb-1">Qtd</label>
                <input type="number" id="mao_obra_qtd" class="form-control request" value="1" min="1" step="1">
            </div>

            <div class="col-md-3">
                <button type="button" id="btnAddMaoObra" class="btn btn-primary btn-icon-split">
                    <span class="icon text-white-50">
                        <i class="fas fa-plus"></i>
                    </span>
                    <span class="text">Adicionar</span>
                </button>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm" id="tableMaoObra">
            <thead>
                <tr>
                    <th style="width: 10%;">Ações</th>
                    <th>Tipo</th>
                    <th>Mão de Obra</th>
                    <th style="width: 10%;">Qtd</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>