<div class="modal fade" id="modal_modelo_relatorio" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            {{-- Header --}}
            <div class="modal-header">
                <h5 class="modal-title text-primary font-weight-bold" id="modal_modelo_relatorio_label">
                    Modelos de Relatórios | Novo
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            {{-- Body --}}
            <div class="modal-body ui-front">
                <form id="form_modelo_relatorio" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="mod_rel_method" value="POST">
                    <input type="hidden" id="mod_rel_id" name="mod_rel_id">

                    {{-- 1) Descrição --}}
                    <div class="form-group row">
                        <label for="mod_rel_descricao" class="col-sm-2 col-form-label font-weight-bold">
                            Descrição:
                        </label>
                        <div class="col-sm-10">
                            <input type="text"
                                class="form-control"
                                id="mod_rel_descricao"
                                name="mod_rel_descricao"
                                maxlength="50"
                                placeholder="Ex.: Relatório Obra Padrão">
                        </div>
                    </div>

                    {{-- 2) Tipo de Data (radio) --}}
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label font-weight-bold">Tipo de Data:</label>
                        <div class="col-sm-10 d-flex align-items-center">
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="mod_rel_tp_data_0" name="mod_rel_tp_data"
                                    class="custom-control-input" value="0">
                                <label class="custom-control-label" for="mod_rel_tp_data_0">
                                    Relatório Diário
                                </label>
                            </div>

                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="mod_rel_tp_data_1" name="mod_rel_tp_data"
                                    class="custom-control-input" value="1">
                                <label class="custom-control-label" for="mod_rel_tp_data_1">
                                    Relatório Período
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- 3) Entrega Técnica (switch) --}}
                    <x-switch-entrega-tecnica />

                    {{-- 4) Itens do Relatório --}}
                    <x-checkbox-itens-relatorio />

                    {{-- 4) Ativo (somente ao editar) --}}
                    <div class="form-group row d-none mt-3" id="div_mod_rel_ativo">
                        <label for="mod_rel_ativo" class="col-sm-2 col-form-label font-weight-bold">Ativo:</label>
                        <div class="col-sm-10">
                            <div class="custom-control custom-checkbox col-form-label">
                                <input type="checkbox" class="custom-control-input" id="mod_rel_ativo" name="mod_rel_ativo" checked>
                                <label class="custom-control-label" for="mod_rel_ativo" id="mod_rel_ativo_label">Ativo</label>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="modal-footer p-0 pt-3">
                        <button type="submit" class="btn btn-success">Salvar</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>