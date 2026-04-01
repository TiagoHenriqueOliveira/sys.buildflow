<div class="modal fade" id="modal_atendimento" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title text-primary font-weight-bold" id="modal_atendimento_label">
                    Atendimentos | Novo
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body ui-front">
                <form id="form_atendimento" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="aten_method" value="POST">
                    <input type="hidden" name="aten_id" id="aten_id">

                    <x-select-tipo-atendimento :tipos="$tiposAtendimentos" />

                    <x-select-natureza-atendimento :naturezas="$naturezasAtendimentos" />

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Obra:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="aten_descricao" name="aten_descricao" maxlength="50"
                                placeholder="Ex.: Nestlé Purina">
                        </div>
                    </div>

                    <x-select-usuario :usuarios="$usuarios" />
                    <x-autocomplete-cliente />

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Nº Proposta:</label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" id="aten_nr_proposta" name="aten_nr_proposta" maxlength="20">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Responsável:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="aten_responsavel" name="aten_responsavel" maxlength="50">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Endereço:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="aten_endereco" name="aten_endereco" maxlength="100">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Período:</label>
                        <div class="col-sm-4">
                            <input type="date" class="form-control" id="aten_dt_inicio" name="aten_dt_inicio">
                        </div>
                        <div class="col-sm-4">
                            <input type="date" class="form-control" id="aten_dt_fim" name="aten_dt_fim">
                        </div>
                    </div>

                    <x-radio-status-atendimento />

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