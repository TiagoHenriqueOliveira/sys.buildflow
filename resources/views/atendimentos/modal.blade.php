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
                        <a class="nav-link disabled" id="tab-observacoes-tab" data-toggle="tab" href="#tab-observacoes" role="tab">
                            Observações
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-equipamentos-tab" data-toggle="tab" href="#tab-equipamentos" role="tab" disabled>
                            Equipamentos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link disabled" id="tab-anexos-aten-tab" data-toggle="tab" href="#tab-anexos-aten" role="tab">
                            Anexos
                        </a>
                    </li>
                </ul>

                {{-- Formulário envolve Dados + Observações para compartilhar o mesmo submit --}}
                <form id="form_atendimento" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="aten_method" value="POST">
                    <input type="hidden" name="aten_id" id="aten_id">

                    <div class="tab-content" id="modalAbaAtendimentoContent">
                        <!-- Tab Dados -->
                        <div class="tab-pane fade show active" id="tab-dados" role="tabpanel">
                            <x-select-natureza-atendimento :naturezas="$naturezasAtendimentos" />

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

                            <div class="form-group row align-items-center">
                                <label class="col-sm-3 col-form-label font-weight-bold">Telefone:</label>
                                <div class="col-sm-5">
                                    <input type="text" class="form-control" id="aten_telefone" name="aten_telefone" maxlength="20" placeholder="(00) 00000-0000">
                                </div>
                                <div class="col-sm-2">
                                    <a id="btnWhatsapp" href="#" target="_blank" class="btn btn-success" title="Abrir WhatsApp">
                                        <i class="fab fa-whatsapp fa-lg"></i>
                                    </a>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label font-weight-bold">Endereço:</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" id="aten_endereco" name="aten_endereco" maxlength="100">
                                </div>
                            </div>

                            <div class="form-group row align-items-center">
                                <label class="col-sm-3 col-form-label font-weight-bold">Entrega Téc.:</label>
                                <div class="col-sm-9 d-flex align-items-center">
                                    <div class="custom-control custom-switch mr-3">
                                        <input type="checkbox"
                                            class="custom-control-input"
                                            id="aten_entrega_tecnica"
                                            name="aten_entrega_tecnica"
                                            value="1">
                                        <label class="custom-control-label" for="aten_entrega_tecnica"></label>
                                    </div>
                                    <span class="badge badge-secondary" id="aten_entrega_tecnica_badge">Não</span>
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
                        </div>

                        <!-- Tab Observações -->
                        <div class="tab-pane fade" id="tab-observacoes" role="tabpanel">
                            <div class="form-group">
                                <label class="font-weight-bold">Técnicas:</label>
                                <textarea class="form-control" id="aten_obs_tecnica" name="aten_obs_tecnica" rows="4"></textarea>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold">Cliente:</label>
                                <textarea class="form-control" id="aten_obs_cliente" name="aten_obs_cliente" rows="4"></textarea>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold">Manutenção:</label>
                                <textarea class="form-control" id="aten_obs_manutencao" name="aten_obs_manutencao" rows="4"></textarea>
                            </div>
                        </div>

                        <!-- Tab Equipamentos -->
                        <div class="tab-pane fade" id="tab-equipamentos" role="tabpanel">
                            @include('atendimentos.components.form-equipamento')

                            <div class="form-group row">
                                <div class="col-sm-9 ml-auto">
                                    <button type="button" id="btnAdicionarEquipamento" class="btn btn-success btn-sm">
                                        <i class="fas fa-plus"></i> Adicionar
                                    </button>
                                </div>
                            </div>

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
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab Anexos -->
                        <div class="tab-pane fade" id="tab-anexos-aten" role="tabpanel">
                            <div class="file-upload-box mb-3">
                                <div class="file-upload-group">
                                    <label class="btn btn-primary btn-sm file-upload-button mb-0">
                                        <i class="fas fa-upload"></i>
                                        <input type="file" class="file-upload-input" id="aten_anexo_file" multiple accept="*/*">
                                    </label>
                                    <span class="file-upload-text" id="aten_anexo_nome">Selecione arquivos para upload</span>
                                    <button type="button" class="btn btn-success btn-sm" id="btnUploadAnexoAten">
                                        <i class="fas fa-cloud-upload-alt"></i> Enviar
                                    </button>
                                </div>
                            </div>
                            <div id="aten_anexos_lista"></div>
                        </div>
                    </div>

                    {{-- Botão compartilhado entre Dados e Observações --}}
                    <x-modal-footer />
                </form>
            </div>
        </div>
    </div>
</div>
