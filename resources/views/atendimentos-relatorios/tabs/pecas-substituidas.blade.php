{{-- PEÇAS SUBSTITUÍDAS --}}
<div id="tab-pecas" role="tabpanel" x-show="tab === 'pecas'">
    <div class="mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label for="peca_descricao" class="fw-bold mb-1">Descrição da Peça</label>
                <input type="text"
                    id="peca_descricao"
                    class="form-control"
                    maxlength="255"
                    placeholder="Descreva a peça substituída">
            </div>

            <div class="col-md-2">
                <button type="button" id="btnAddPeca" class="btn btn-primary btn-icon-split">
                    <span class="icon text-white-50">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
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


</div>
