{{-- DESCRIÇÃO — RF001: lista de itens (texto + foto opcional), mesmo padrão de Peças Substituídas --}}
<div class="tab-pane fade" id="tab-descricao" role="tabpanel">
    <div id="descricaoFormNovo" class="mb-3">
        <textarea id="descricao_item_texto" class="form-control mb-2" rows="2"
            placeholder="Descreva o item..."></textarea>

        <div class="file-upload-box">
            <div class="file-upload-group">
                <label class="btn btn-primary btn-sm file-upload-button mb-0">
                    <i class="fas fa-upload"></i>
                    <input type="file" class="file-upload-input" id="descricao_item_foto" accept=".jpg,.jpeg,.png,.webp,.gif" multiple>
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
