{{-- FERRAMENTAS --}}
<div class="tab-pane fade" id="tab-ferramentas">
    <div class="mb-3">
        <div class="form-row align-items-end">
            <div class="col-md-7">
                <label for="equip_label" class="font-weight-bold mb-1">Pesquisar</label>
                <input type="text" id="equip_label" class="form-control request" placeholder="Digite ao menos 3 caracteres">
                <input type="hidden" id="equip_id" class="request">
            </div>

            <div class="col-md-2">
                <label for="equip_qtd" class="font-weight-bold mb-1">Qtd</label>
                <input type="number" id="equip_qtd" class="form-control request" value="1" min="1" step="1">
            </div>

            <div class="col-md-3">
                <button type="button" id="btnAddEquip" class="btn btn-primary btn-icon-split">
                    <span class="icon text-white-50">
                        <i class="fas fa-plus"></i>
                    </span>
                    <span class="text">Adicionar</span>
                </button>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-striped" id="tableEquip">
            <thead>
                <tr>
                    <th style="width: 10%;">Ações</th>
                    <th>Ferramenta</th>
                    <th style="width: 10%;">Qtd</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>