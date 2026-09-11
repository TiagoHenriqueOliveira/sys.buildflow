{{-- OBSERVAÇÃO INTERNA (BF11) — nível do relatório (não do atendimento,
     diferente de aten_obs_tecnica/aten_obs_cliente/aten_obs_manutencao que
     já existiam). Nunca aparece no PDF assinado, ver pdf.blade.php. --}}
<div id="tab-observacao-interna" role="tabpanel" x-show="tab === 'observacao-interna'">
    <div class="sbadmin-alert sbadmin-alert-info mb-3" role="alert">
        <i class="bi bi-eye-slash" aria-hidden="true"></i>
        Esta observação é apenas para uso interno e nunca aparece no PDF assinado do relatório.
    </div>
    <form id="form_observacao_interna">
        <div class="form-group">
            <label class="fw-bold" for="aten_rel_observacao_interna">Observação Interna</label>
            <textarea class="form-control" id="aten_rel_observacao_interna" name="valor" rows="8"
                placeholder="Observações internas sobre este relatório...">{{ $atendimentoRelatorio->aten_rel_observacao_interna }}</textarea>
        </div>
    </form>
</div>