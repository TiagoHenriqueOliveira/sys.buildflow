{{-- Confirmação genérica de exclusão — aberta por confirmarAcao() em
     public/js/app/atendimentos.relatorios.js (dias e itens da Descrição). --}}
<div class="modal fade" id="modal_confirmar_acao" tabindex="-1" role="dialog" aria-labelledby="modal_confirmar_acao_label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary font-weight-bold" id="modal_confirmar_acao_label">Confirmar exclusão</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body" id="modal_confirmar_acao_texto"></div>

            <div class="modal-footer">
                <button type="button" class="btn btn-danger btn-icon-split" id="btnConfirmarAcao">
                    <span class="icon text-white-50">
                        <i class="fas fa-trash"></i>
                    </span>
                    <span class="text">Excluir</span>
                </button>
                <button type="button" class="btn btn-secondary btn-icon-split" data-dismiss="modal">
                    <span class="icon text-white-50">
                        <i class="fas fa-times"></i>
                    </span>
                    <span class="text">Cancelar</span>
                </button>
            </div>
        </div>
    </div>
</div>
