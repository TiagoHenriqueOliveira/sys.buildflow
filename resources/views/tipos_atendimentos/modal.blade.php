<div class="modal fade" id="modal_tipo_atendimento" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            {{-- Header --}}
            <div class="modal-header">
                <h5 class="modal-title text-primary font-weight-bold" id="modal_tipo_atendimento_label">
                    Tipos de Atendimentos | Novo
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            {{-- Body --}}
            <div class="modal-body ui-front">
                <form id="form_tipo_atendimento" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="tp_aten_method" value="POST">
                    <input type="hidden" id="tp_aten_id" name="tp_aten_id">

                    <div class="form-group row">
                        <label for="tp_aten_descricao" class="col-sm-3 col-form-label font-weight-bold">
                            Descrição:
                        </label>
                        <div class="col-sm-9">
                            <input type="text"
                                class="form-control"
                                id="tp_aten_descricao"
                                name="tp_aten_descricao"
                                maxlength="30"
                                placeholder="Ex.: Atendimento Técnico">
                        </div>
                    </div>

                    {{-- Select Naturezas (somente ativas) --}}
                    <x-select-naturezas-atendimentos :naturezas="$naturezasAtivas" />

                    {{-- Ativo somente ao editar --}}
                    <div class="form-group row d-none" id="div_tp_aten_ativo">
                        <label for="tp_aten_ativo" class="col-sm-3 col-form-label font-weight-bold">
                            Ativo:
                        </label>
                        <div class="col-sm-9">
                            <div class="custom-control custom-checkbox col-form-label">
                                <input type="checkbox"
                                    class="custom-control-input"
                                    id="tp_aten_ativo"
                                    name="tp_aten_ativo"
                                    checked>
                                <label class="custom-control-label" for="tp_aten_ativo" id="tp_aten_ativo_label">
                                    Ativo
                                </label>
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