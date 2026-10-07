{{-- OCORRÊNCIAS --}}
<div class="tab-pane fade" id="tab-ocorrencias">
    <div class="mb-3">
        <div class="form-row align-items-end">
            <div class="col-md-6">
                <label for="ocorrencia_id" class="font-weight-bold mb-1">Ocorrência</label>
                <select id="ocorrencia_id" class="form-control">
                    <option value="">Selecione...</option>
                    @foreach($ocorrencias as $ocorrencia)
                        <option value="{{ $ocorrencia->ocor_id }}">{{ $ocorrencia->ocor_descricao }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label for="ocorrencia_observacao" class="font-weight-bold mb-1">Observação</label>
                <input type="text"
                    id="ocorrencia_observacao"
                    class="form-control"
                    maxlength="255"
                    placeholder="Opcional">
            </div>

            <div class="col-md-2">
                <button type="button" id="btnAddOcorrencia" class="btn btn-primary btn-icon-split">
                    <span class="icon text-white-50">
                        <i class="fas fa-plus"></i>
                    </span>
                    <span class="text">Adicionar</span>
                </button>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-striped" id="tableOcorrencias">
            <thead>
                <tr>
                    <th style="width:10%;">Ações</th>
                    <th>Ocorrência</th>
                    <th style="width:30%;">Observação</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
