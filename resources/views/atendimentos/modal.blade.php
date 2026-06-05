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
                <!-- Nav tabs -->
                <ul class="nav nav-tabs mb-3" id="modalAbaAtendimento" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-dados-tab" data-toggle="tab" href="#tab-dados" role="tab">
                            Dados
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-equipamentos-tab" data-toggle="tab" href="#tab-equipamentos" role="tab" disabled>
                            Equipamentos
                        </a>
                    </li>
                </ul>

                <!-- Tab content -->
                <div class="tab-content" id="modalAbaAtendimentoContent">
                    <!-- Tab Dados -->
                    <div class="tab-pane fade show active" id="tab-dados" role="tabpanel">
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

                            <x-modal-footer />
                        </form>
                    </div>

                    <!-- Tab Equipamentos -->
                    <div class="tab-pane fade" id="tab-equipamentos" role="tabpanel">
                        <form id="form_equip_atendimento" method="POST">
                            @csrf
                            <input type="hidden" name="aten_id" id="form_equip_aten_id">

                            @include('atendimentos.components.form-equipamento')

                            <div class="form-group row">
                                <div class="col-sm-9 ml-auto">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-plus"></i> Adicionar
                                    </button>
                                </div>
                            </div>
                        </form>

                        <hr>

                        <div class="row">
                            <div class="col-12">
                                <h6 class="font-weight-bold">Equipamentos Cadastrados</h6>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-hover" id="table_equipamentos">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="text-center" style="width: 50px;">Ações</th>
                                        <th>Descrição</th>
                                        <th>Observações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>