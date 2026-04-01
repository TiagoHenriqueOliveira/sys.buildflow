<div class="modal fade" id="modal_mao_de_obra" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary font-weight-bold" id="modal_mao_de_obra_label">
                    Mão de Obra | Novo
                </h5>

                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body ui-front">
                <form id="form_mao_de_obra" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="ocup_method" value="POST">
                    <input type="hidden" id="ocup_id" name="ocup_id">

                    <div class="form-group row">
                        <label for="ocup_descricao" class="col-sm-3 col-form-label font-weight-bold">
                            Descrição:
                        </label>
                        <div class="col-sm-9">
                            <input type="text"
                                class="form-control"
                                id="ocup_descricao"
                                name="ocup_descricao"
                                maxlength="50"
                                placeholder="Ex.: Técnico de Instalação">
                        </div>
                    </div>

                    <x-select-tipos-ocupacoes :tipos="$tiposOcupacoesAtivos" />

                    <div class="form-group row d-none" id="div_ocup_ativo">
                        <label for="ocup_ativo" class="col-sm-3 col-form-label font-weight-bold">
                            Ativo:
                        </label>
                        <div class="col-sm-9">
                            <div class="custom-control custom-checkbox col-form-label">
                                <input type="checkbox"
                                    class="custom-control-input"
                                    id="ocup_ativo"
                                    name="ocup_ativo"
                                    checked>
                                <label class="custom-control-label" for="ocup_ativo" id="ocup_ativo_label">
                                    Ativo
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer p-0 pt-3">
                        <button type="submit" class="btn btn-success btn-icon-split">
                            <span class=" icon text-white-50">
                                <i class="fas fa-save"></i>
                            </span>
                            <span class="text">Salvar</span>
                        </button>

                        <button type="button" class="btn btn-secondary btn-icon-split" data-dismiss="modal">
                            <span class=" icon text-white-50">
                                <i class="fas fa-times"></i>
                            </span>
                            <span class="text">Fechar</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>