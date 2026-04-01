<div class="modal fade" id="modal_usuario" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary font-weight-bold" id="modal_usuario_label">
                    Usuários | Novo
                </h5>

                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body ui-front">
                <form id="form_usuario" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="user_method" value="POST">
                    <input type="hidden" id="user_id" name="user_id">

                    <div class="form-group row">
                        <label for="user_nome" class="col-sm-3 col-form-label font-weight-bold">Nome:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control"
                                id="user_nome" name="user_nome"
                                maxlength="50" placeholder="Ex.: João da Silva">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="user_email" class="col-sm-3 col-form-label font-weight-bold">E-mail:</label>
                        <div class="col-sm-9">
                            <input type="email" class="form-control"
                                id="user_email" name="user_email"
                                maxlength="100" placeholder="Ex.: joao@email.com">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Nível:</label>
                        <div class="col-sm-9">
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="nivel_admin" name="user_nivel_acesso"
                                    class="custom-control-input" value="0">
                                <label class="custom-control-label" for="nivel_admin">Administrador</label>
                            </div>

                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="nivel_tecnico" name="user_nivel_acesso"
                                    class="custom-control-input" value="1">
                                <label class="custom-control-label" for="nivel_tecnico">Técnico</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="user_senha" class="col-sm-3 col-form-label font-weight-bold">
                            Senha:
                        </label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <input type="password" class="form-control"
                                    id="user_senha" name="user_senha"
                                    maxlength="50"
                                    placeholder="Informe uma senha">

                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-primary" id="btnSugerirSenha">
                                        Sugerir
                                    </button>
                                </div>

                                <div class="input-group-append">
                                    <button type="button"
                                        class="btn btn-outline-secondary btn-toggle-password"
                                        data-target="#user_senha">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="form-text text-muted" id="senha_help">
                                No cadastro a senha é obrigatória. Na edição, preencha apenas se desejar alterá-la.
                            </small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="user_senha_confirmation" class="col-sm-3 col-form-label font-weight-bold">
                            Confirmar:
                        </label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <input type="password" class="form-control"
                                    id="user_senha_confirmation"
                                    name="user_senha_confirmation"
                                    maxlength="50"
                                    placeholder="Confirme a senha">

                                <div class="input-group-append">
                                    <button type="button"
                                        class="btn btn-outline-secondary btn-toggle-password"
                                        data-target="#user_senha_confirmation">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row d-none" id="div_user_ativo">
                        <label for="user_ativo" class="col-sm-3 col-form-label font-weight-bold">Ativo:</label>
                        <div class="col-sm-9">
                            <div class="custom-control custom-checkbox col-form-label">
                                <input type="checkbox" class="custom-control-input"
                                    id="user_ativo" name="user_ativo" checked>
                                <label class="custom-control-label" for="user_ativo" id="user_ativo_label">Ativo</label>
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