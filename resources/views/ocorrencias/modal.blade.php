<div class="modal fade" id="modal_ocorrencia" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary font-weight-bold" id="modal_ocorrencia_label">
                    Ocorrências | Novo
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="form_ocorrencia" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="ocor_method" value="POST">
                    <input type="hidden" id="ocor_id" name="ocor_id">

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">
                            Descrição:
                        </label>
                        <div class="col-sm-9">
                            <input type="text"
                                class="form-control"
                                id="ocor_descricao"
                                name="ocor_descricao"
                                maxlength="50"
                                placeholder="Ex.: Falha no equipamento">
                        </div>
                    </div>

                    <div class="form-group row d-none" id="div_ocor_ativo">
                        <label class="col-sm-3 col-form-label font-weight-bold">
                            Ativo:
                        </label>
                        <div class="col-sm-9">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox"
                                    class="custom-control-input"
                                    id="ocor_ativo"
                                    name="ocor_ativo"
                                    checked>
                                <label class="custom-control-label" for="ocor_ativo" id="ocor_ativo_label">
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