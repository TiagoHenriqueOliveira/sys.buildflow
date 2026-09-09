<div id="tab-info-adicionais" role="tabpanel" x-show="tab === 'info-adicionais'">
    <form id="form_informacoes_adicionais">
        <div class="form-group">
            <label class="fw-bold">Observações Gerais:</label>
            <textarea class="form-control" id="aten_rel_informacoes_adicionais" name="valor" rows="8"
                placeholder="Informe observações gerais relevantes...">{{ $atendimentoRelatorio->aten_rel_informacoes_adicionais }}</textarea>
        </div>
    </form>
</div>
