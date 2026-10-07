{{-- DESCRIÇÃO — RF001: lista de itens (texto + no máximo 1 foto; o texto é
     um comentário da foto), mesmo padrão de Peças Substituídas --}}
<div class="tab-pane fade" id="tab-descricao" role="tabpanel">
    <div id="descricaoFormNovo" class="mb-3">
        <textarea id="descricao_item_texto" class="form-control mb-2" rows="2"
            placeholder="Descreva o item..."></textarea>

        <div class="file-upload-box">
            <div class="file-upload-group">
                <label class="btn btn-primary btn-sm file-upload-button mb-0">
                    <i class="fas fa-upload"></i>
                    <input type="file" class="file-upload-input" id="descricao_item_foto" accept=".jpg,.jpeg,.png,.webp,.gif">
                </label>
                <span class="file-upload-text">Nenhuma foto selecionada</span>
                <button type="button" id="btnAddDescricaoItem" class="btn btn-primary btn-icon-split">
                    <span class="icon text-white-50">
                        <i class="fas fa-plus"></i>
                    </span>
                    <span class="text">Adicionar</span>
                </button>
            </div>
        </div>
    </div>

    {{-- RF004: retrocompatibilidade — relatório antigo (sem itens novos) mostra o texto legado, somente leitura --}}
    <div id="descricaoLegadoBox" class="readonly-field" style="display:none; white-space:pre-wrap;"></div>

    <div id="listaDescricaoItens" class="row"></div>
    <div id="descricaoItensVazio" class="text-muted" style="display:none;">Nenhum item de descrição adicionado.</div>
</div>

{{-- RF013 — editar item: texto, incluir/substituir ou remover a foto --}}
<div class="modal fade" id="modal_descricao_item" tabindex="-1" role="dialog" aria-labelledby="modal_descricao_item_label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary font-weight-bold" id="modal_descricao_item_label">Editar item</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="form_descricao_item" autocomplete="off">
                    <div class="form-group">
                        <label for="descricao_edit_texto" class="font-weight-bold mb-1">Texto</label>
                        <textarea id="descricao_edit_texto" class="form-control" rows="4" required></textarea>
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold mb-1">Foto</label>
                        <div id="descricao_edit_foto_atual" class="mb-2"></div>

                        <div class="file-upload-box">
                            <div class="file-upload-group">
                                <label class="btn btn-primary btn-sm file-upload-button mb-0">
                                    <i class="fas fa-upload"></i>
                                    <input type="file" class="file-upload-input" id="descricao_edit_foto" accept=".jpg,.jpeg,.png,.webp">
                                </label>
                                <span class="file-upload-text">Nenhuma foto selecionada</span>
                            </div>
                        </div>
                        <small class="text-muted" id="descricao_edit_foto_ajuda"></small>

                        <div class="custom-control custom-checkbox mt-2" id="descricao_edit_remover_box">
                            <input type="checkbox" class="custom-control-input" id="descricao_edit_remover">
                            <label class="custom-control-label" for="descricao_edit_remover">Remover foto</label>
                        </div>
                    </div>

                    <div class="modal-footer p-0 pt-3">
                        <button type="submit" class="btn btn-success btn-icon-split">
                            <span class="icon text-white-50">
                                <i class="fas fa-save"></i>
                            </span>
                            <span class="text">Salvar</span>
                        </button>
                        <button type="button" class="btn btn-secondary btn-icon-split" data-dismiss="modal">
                            <span class="icon text-white-50">
                                <i class="fas fa-times"></i>
                            </span>
                            <span class="text">Cancelar</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
