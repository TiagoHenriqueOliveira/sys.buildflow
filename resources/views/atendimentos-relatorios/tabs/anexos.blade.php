{{-- ANEXOS — pedido do cliente (2026-09-14): permitir somente fotos, com o
     mesmo layout da aba Anexos do cadastro de Atendimento (botão de upload +
     nome do(s) arquivo(s) selecionado(s) + botão "Enviar" à direita, sem o
     accordion de 3 seções Arquivos/Fotos/Vídeos que existia antes). Arquivos
     e vídeos enviados ANTES dessa mudança continuam listados (dado real, não
     descartado), só sem accordion e sem upload novo. --}}
<div id="tab-anexos" role="tabpanel" x-show="tab === 'anexos'">
    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <label class="btn btn-primary btn-sm mb-0">
            <i class="bi bi-upload" aria-hidden="true"></i>
            <input id="uploadFotosInput" name="fotos[]" type="file" class="d-none" accept="image/*" multiple>
        </label>
        <span class="text-body-secondary small" id="uploadFotosNome">Selecione fotos para upload</span>
        <button type="button" class="btn btn-success btn-sm ms-auto" id="btnEnviarAnexosRelatorio">
            <i class="bi bi-cloud-upload" aria-hidden="true"></i> Enviar
        </button>
    </div>

    <div id="anexosFotosList">
        <div class="d-flex flex-wrap" id="anexosFotosContainer"></div>
    </div>

    @if((!empty($atendimentoRelatorio->anexos) && $atendimentoRelatorio->anexos->count()) || (!empty($atendimentoRelatorio->videos) && $atendimentoRelatorio->videos->count()))
        <hr class="my-3">
        <p class="text-body-secondary small mb-2">Anexos enviados antes desta tela passar a aceitar somente fotos:</p>
        <div id="anexosArquivosList" class="mb-2"></div>
        <div class="d-flex flex-wrap" id="anexosVideosContainer"></div>
    @endif
</div>