{{-- Modal de confirmacao generico -- substitui o confirm() nativo do
     navegador em qualquer acao destrutiva/irreversivel (excluir, etc).
     Estado global via Alpine.store('confirmacao'), controlado pelo helper
     JS window.confirmar(mensagem, opcoes) (ver resources/js/confirm-modal.js
     do app consumidor). Montado uma unica vez no layout base, disponivel
     em qualquer pagina sem precisar declarar nada a mais. --}}
<div class="modal-backdrop show" x-show="$store.confirmacao.aberto" x-cloak></div>
<div
    class="modal"
    :class="{ show: $store.confirmacao.aberto }"
    x-show="$store.confirmacao.aberto"
    style="display: block"
    x-cloak
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    @keydown.escape.window="$store.confirmacao.cancelar()"
    @click.self="$store.confirmacao.cancelar()"
>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmação</h5>
                <button type="button" class="btn-close" aria-label="Fechar" @click="$store.confirmacao.cancelar()"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" x-text="$store.confirmacao.mensagem"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="$store.confirmacao.cancelar()">Cancelar</button>
                <button
                    type="button"
                    class="btn"
                    :class="`btn-${$store.confirmacao.tipoConfirmar}`"
                    @click="$store.confirmacao.confirmar()"
                    x-text="$store.confirmacao.rotuloConfirmar"
                ></button>
            </div>
        </div>
    </div>
</div>
