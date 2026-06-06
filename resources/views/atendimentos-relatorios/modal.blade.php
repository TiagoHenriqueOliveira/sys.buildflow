<div class="modal fade" id="modal_relatorio" tabindex="-1" role="dialog" aria-labelledby="modal_relatorio_label" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary font-weight-bold" id="modal_relatorio_label">
                    Relatório de Atendimento
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body ui-front">
                <form id="form_relatorio" autocomplete="off">
                    <div class="form-group">
                        <label for="rel_aten_label" class="font-weight-bold mb-1">Atendimento</label>

                        <input type="hidden" id="rel_aten_id" name="aten_id">

                        <input
                            type="text"
                            class="form-control"
                            id="rel_aten_label"
                            name="aten_label"
                            placeholder="Digite para buscar..."
                            autocomplete="off">
                        <small class="text-muted">
                            Busque pelo nome do cliente ou descrição da obra.
                        </small>
                    </div>

                    <div class="form-group mb-0">
                        <label for="rel_data" class="font-weight-bold mb-1">Data do relatório</label>
                        <input
                            type="date"
                            class="form-control"
                            id="rel_data"
                            name="aten_rel_data"
                            value="{{ now()->format('Y-m-d') }}"
                            max="{{ now()->format('Y-m-d') }}">
                    </div>

                    <x-modal-footer />
                </form>
            </div>
        </div>
    </div>
</div>