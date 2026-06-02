{{-- COMENTÁRIOS --}}
<div class="tab-pane fade" id="tab-comentarios">
    <div class="mb-3">
        <button type="button" id="btnNovoComentario" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50">
                <i class="fas fa-plus"></i>
            </span>
            <span class="text">Adicionar</span>
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-striped" id="tableComentarios">
            <thead>
                <tr>
                    <th style="width:10%;">Ações</th>
                    <th>Comentário</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalComentario" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Comentário</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="formComentario">
                <div class="modal-body request">
                    <input type="hidden" id="aten_rel_com_id">

                    <div class="form-group">
                        <label for="aten_rel_com_descricao">Comentário</label>
                        <textarea
                            id="aten_rel_com_descricao"
                            class="form-control request"
                            rows="4"
                            maxlength="250"
                            placeholder="Digite o comentário..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-icon-split" data-dismiss="modal">
                        <span class="icon text-white-50">
                            <i class="fas fa-times"></i>
                        </span>
                        <span class="text">Cancelar</span>
                    </button>

                    <button type="submit" class="btn btn-success btn-icon-split" id="btnSalvarComentario">
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