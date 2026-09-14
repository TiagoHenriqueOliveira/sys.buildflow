{{-- ABA DE SESSÃO (pedido do cliente, 2026-09-14) — uma pergunta marcada
     como "Sessão" no Configurador vira esta aba, com o nome definido no
     cadastro. As perguntas de verdade que ficam dentro dela chegam via o
     mesmo endpoint /respostas de tabs/perguntas.blade.php, já agrupadas
     pelo backend (ConfigModelo::perguntasAgrupadasPorSessao()) — ver JS em
     show.blade.php (renderizarPerguntasRelatorio). --}}
<div id="tab-sessao-{{ $sessao->cfg_perg_id }}" role="tabpanel" x-show="tab === 'sessao-{{ $sessao->cfg_perg_id }}'">
    <div id="listaPerguntasRelatorio-{{ $sessao->cfg_perg_id }}" class="lista-perguntas-sessao">
        <p class="text-body-secondary mb-0">Carregando perguntas...</p>
    </div>
</div>