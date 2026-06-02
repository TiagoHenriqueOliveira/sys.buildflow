{{-- ATIVIDADES --}}
<div class="tab-pane fade" id="tab-atividades">
    <div class="mb-3">
        <button type="button" id="btnNovaAtividade" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50">
                <i class="fas fa-plus"></i>
            </span>
            <span class="text">Adicionar</span>
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-striped" id="tableAtividades">
            <thead>
                <tr>
                    <th style="width:10%;">Ações</th>
                    <th>Descrição</th>
                    <th style="width:15%;">Status</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalAtividade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Atividade</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="formAtividade">
                <div class="modal-body request">
                    <input type="hidden" id="aten_rel_ativ_id">

                    <div class="form-group">
                        <label for="aten_rel_ativ_descricao">Descrição</label>
                        <textarea
                            id="aten_rel_ativ_descricao"
                            class="form-control request"
                            rows="4"
                            maxlength="255"
                            placeholder="Descreva a atividade..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="aten_rel_ativ_status">Status</label>
                        <select id="aten_rel_ativ_status" class="form-control request">
                            <option value="0">Não iniciada</option>
                            <option value="1">Iniciada</option>
                            <option value="2">Em andamento</option>
                            <option value="3">Concluída</option>
                            <option value="4">Paralisada</option>
                            <option value="5">Não executada</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-icon-split" data-dismiss="modal">
                        <span class="icon text-white-50">
                            <i class="fas fa-times"></i>
                        </span>
                        <span class="text">Cancelar</span>
                    </button>

                    <button type="submit" class="btn btn-success btn-icon-split" id="btnSalvarAtividade">
                        <span class="icon text-white-50">
                            <i class="fas fa-save"></i>
                        </span>
                        <span class="text">Salvar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>