<div class="modal fade" id="modal_atendimento" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            {{-- Header --}}
            <div class="modal-header">
                <h5 class="modal-title text-primary font-weight-bold" id="modal_atendimento_label">
                    Atendimentos | Novo
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            {{-- Body --}}
            <div class="modal-body">
                <form id="form_atendimento" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="aten_method" value="POST">
                    <input type="hidden" name="aten_id" id="aten_id">

                    {{-- Tipo de Atendimento --}}
                    <x-select-tipo-atendimento :tipos="$tiposAtendimentos" />

                    {{-- Cliente (autocomplete) --}}
                    <x-autocomplete-cliente />

                    {{-- Usuário --}}
                    <x-select-usuario :usuarios="$usuarios" />

                    {{-- Status (radio inline) --}}
                    <x-radio-status-atendimento />

                    {{-- Período --}}
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">
                            Período:
                        </label>
                        <div class="col-sm-4">
                            <input type="date"
                                class="form-control"
                                id="aten_dt_inicio"
                                name="aten_dt_inicio">
                        </div>
                        <div class="col-sm-4">
                            <input type="date"
                                class="form-control"
                                id="aten_dt_fim"
                                name="aten_dt_fim">
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