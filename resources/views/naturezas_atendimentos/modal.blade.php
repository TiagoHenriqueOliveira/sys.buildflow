{{-- Modal Naturezas de Atendimento --}}
<div class="modal fade" id="modal_natureza_atendimento" tabindex="-1" role="dialog" aria-labelledby="modalNaturezaAtendimentoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            {{-- Header --}}
            <div class="modal-header">
                <h5 class="modal-title text-primary font-weight-bold" id="modal_natureza_atendimento_label">
                    Naturezas de Atendimento | Novo
                </h5>

                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            {{-- Body --}}
            <div class="modal-body ui-front">
                <form id="form_natureza_atendimento" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="nat_aten_method" value="POST">
                    <input type="hidden" id="nat_aten_id" name="nat_aten_id">

                    <div class="form-group row">
                        <label for="nat_aten_descricao" class="col-sm-3 col-form-label font-weight-bold">
                            Descrição:
                        </label>
                        <div class="col-sm-9">
                            <input type="text"
                                class="form-control"
                                id="nat_aten_descricao"
                                name="nat_aten_descricao"
                                maxlength="50"
                                placeholder="Ex.: Visita Técnica">
                        </div>
                    </div>

                    {{-- Tipo de Atendimento --}}
                    <x-select-tipo-atendimento :tipos="$tiposAtivos" />

                    {{-- Modelo de Relatório --}}
                    <x-select-modelo-relatorio :modelosRelatorios="$modelosRelatorios" />

                    {{-- Ativo: somente visível ao editar --}}
                    <div class="form-group row d-none" id="div_nat_aten_ativo">
                        <label for="nat_aten_ativo" class="col-sm-3 col-form-label font-weight-bold">
                            Ativo:
                        </label>
                        <div class="col-sm-9">
                            <div class="custom-control custom-checkbox col-form-label">
                                <input type="checkbox"
                                    class="custom-control-input"
                                    id="nat_aten_ativo"
                                    name="nat_aten_ativo"
                                    checked>
                                <label class="custom-control-label"
                                    for="nat_aten_ativo"
                                    id="nat_aten_ativo_label">
                                    Ativo
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="modal-footer p-0 pt-3">
                        <button type="submit" class="btn btn-success">
                            Salvar
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            Fechar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>