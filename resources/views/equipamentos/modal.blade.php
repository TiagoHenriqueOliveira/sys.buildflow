<div class="modal fade" id="modal_equipamento" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title text-primary font-weight-bold" id="modal_equipamento_label">
                    Equipamentos | Novo
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="form_equipamento" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="equip_method" value="POST">
                    <input type="hidden" id="equip_id" name="equip_id">

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">
                            Descrição:
                        </label>
                        <div class="col-sm-9">
                            <input type="text"
                                class="form-control"
                                id="equip_descricao"
                                name="equip_descricao"
                                maxlength="50"
                                placeholder="Ex.: Munck">
                        </div>
                    </div>

                    <div class="form-group row d-none" id="div_equip_ativo">
                        <label class="col-sm-3 col-form-label font-weight-bold">
                            Ativo:
                        </label>
                        <div class="col-sm-9">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox"
                                    class="custom-control-input"
                                    id="equip_ativo"
                                    name="equip_ativo"
                                    checked>
                                <label class="custom-control-label" for="equip_ativo" id="equip_ativo_label">
                                    Ativo
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer p-0 pt-3">
                        <button type="submit" class="btn btn-success">Salvar</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>