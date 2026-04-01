<div class="modal fade" id="modal_cliente" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary font-weight-bold" id="modal_cliente_label">
                    Clientes | Novo
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="form_cliente" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="cli_method" value="POST">
                    <input type="hidden" id="cli_id" name="cli_id">

                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label font-weight-bold">
                            Nome:
                        </label>
                        <div class="col-sm-10">
                            <input type="text"
                                class="form-control"
                                id="cli_nome"
                                name="cli_nome"
                                maxlength="100"
                                placeholder="Ex.: Empresa ABC Ltda">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label font-weight-bold">
                            CNPJ:
                        </label>
                        <div class="col-sm-4">
                            <input type="text"
                                class="form-control cnpj"
                                id="cli_cnpj"
                                name="cli_cnpj"
                                maxlength="18"
                                placeholder="00.000.000/0000-00">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label font-weight-bold">
                            Cidade:
                        </label>
                        <div class="col-sm-10">
                            <input type="text"
                                class="form-control"
                                id="cli_cidade"
                                name="cli_cidade"
                                maxlength="100">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label font-weight-bold">
                            UF:
                        </label>
                        <div class="col-sm-2">
                            <input type="text"
                                class="form-control"
                                id="cli_uf"
                                name="cli_uf"
                                maxlength="2"
                                placeholder="Ex.: SC">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label font-weight-bold">
                            Telefone:
                        </label>
                        <div class="col-sm-4">
                            <input type="text"
                                class="form-control tel"
                                id="cli_telefone"
                                name="cli_telefone"
                                maxlength="15"
                                placeholder="(00) 00000-0000">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label font-weight-bold">
                            E-mail:
                        </label>
                        <div class="col-sm-10">
                            <input type="email"
                                class="form-control"
                                id="cli_email"
                                name="cli_email"
                                maxlength="100">
                        </div>
                    </div>

                    <div class="form-group row d-none" id="div_cli_ativo">
                        <label class="col-sm-2 col-form-label font-weight-bold">
                            Ativo:
                        </label>
                        <div class="col-sm-10">
                            <div class="custom-control custom-checkbox col-form-label">
                                <input type="checkbox"
                                    class="custom-control-input"
                                    id="cli_ativo"
                                    name="cli_ativo"
                                    checked>
                                <label class="custom-control-label"
                                    for="cli_ativo"
                                    id="cli_ativo_label">
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