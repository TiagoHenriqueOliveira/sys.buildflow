{{-- PERGUNTAS DO MODELO (NC02/NC03) — reformulacao da sessao 08: substitui
     a antiga aba "Descrição" de texto livre para relatórios NOVOS. Cada
     pergunta do modelo do Configurador vinculado a natureza (BF04) vira um
     card com o widget certo pro tipo (texto livre/escolha única/múltipla) +
     upload de foto opcional quando a pergunta permite anexo (NC03). Sem
     AJAX bundle no form principal (ao contrário de orçamentos) porque aqui
     PRECISA de upload de arquivo por resposta — mesmo padrão AJAX/sub-
     recurso que atendimentos/form.blade.php já usa. --}}
<div id="tab-perguntas" role="tabpanel" x-show="tab === 'perguntas'">
    <div id="listaPerguntasRelatorio">
        <p class="text-body-secondary mb-0">Carregando perguntas...</p>
    </div>
</div>