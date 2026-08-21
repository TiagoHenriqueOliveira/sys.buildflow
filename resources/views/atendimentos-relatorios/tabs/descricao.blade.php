{{-- DESCRIÇÃO — RF001: lista de itens (texto + foto opcional), mesmo padrão de Peças Substituídas --}}
<div class="tab-pane fade" id="tab-descricao" role="tabpanel">
    <div class="mb-3">
        <div class="form-row">
            <div class="col-md-8">
                <label for="descricao_item_texto" class="font-weight-bold mb-1">Descrição do item</label>
                <textarea id="descricao_item_texto" class="form-control" rows="2"
                    placeholder="Descreva o item..."></textarea>
            </div>
            <div class="col-md-3">
                <label for="descricao_item_foto" class="font-weight-bold mb-1">Foto (opcional)</label>
                <input type="file" id="descricao_item_foto" class="form-control-file" accept=".jpg,.jpeg,.png,.webp,.gif">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" id="btnAddDescricaoItem" class="btn btn-primary btn-block" title="Adicionar">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
    </div>

    <div id="listaDescricaoItens" class="row"></div>
    <div id="descricaoItensVazio" class="text-muted" style="display:none;">Nenhum item de descrição adicionado.</div>
</div>
