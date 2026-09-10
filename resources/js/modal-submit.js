/**
 * Feedback visual de "Salvando..." no botão "Salvar" de qualquer modal —
 * mesma ideia do botão "Autenticando..." da tela de login (ver
 * resources/views/login/index.blade.php): ao submeter o formulário, troca o
 * conteúdo do botão por um spinner Bootstrap + "Salvando...", desabilita o
 * próprio botão e também o botão de fechar (X, `.btn-close`) e qualquer
 * botão "Fechar"/"Cancelar" do mesmo modal — assim o usuário não fecha nem
 * reenvia o formulário enquanto o POST/PUT está em andamento.
 *
 * A grande maioria dos modais usa <form method="POST"> tradicional (reload
 * de página inteira em caso de sucesso ou de erro de validação) — para
 * esses, basta chamar setupModalSubmitFeedback() uma vez (feito em
 * resources/js/app.js): o estado desabilitado persiste naturalmente até a
 * próxima página carregar, sem precisar reabilitar nada (mesmo raciocínio
 * do botão de login).
 *
 * O modal de Atendimentos (atendimentos/modal.blade.php) é exceção: seu
 * formulário continua submetendo via fetch()/JSON porque o modal permanece
 * aberto após salvar, sem reload de página (ver comentário no próprio
 * modal e o @push('scripts') de atendimentos/index.blade.php). Por isso seu
 * form fica de fora do setup automático abaixo, e o próprio script daquela
 * tela chama iniciarFeedbackSalvamento()/pararFeedbackSalvamento()
 * manualmente ao redor do fetch, restaurando o botão quando a resposta
 * chega (sucesso ou erro).
 */

function botoesFecharDoModal(modal) {
    if (!modal) return [];

    return Array.from(modal.querySelectorAll('button')).filter((btn) => {
        const texto = btn.textContent.trim().toLowerCase();
        return texto === 'fechar' || texto === 'cancelar';
    });
}

export function iniciarFeedbackSalvamento(form) {
    const modal = form.closest('.modal');
    const submitButton = form.querySelector('button[type="submit"]');
    const closeButton = modal ? modal.querySelector('.btn-close') : null;
    const fecharButtons = botoesFecharDoModal(modal);

    const estado = {
        submitButton,
        htmlOriginal: submitButton ? submitButton.innerHTML : null,
        closeButton,
        fecharButtons,
    };

    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Salvando...';
    }

    if (closeButton) closeButton.disabled = true;
    fecharButtons.forEach((btn) => { btn.disabled = true; });

    return estado;
}

export function pararFeedbackSalvamento(estado) {
    if (!estado) return;

    if (estado.submitButton) {
        estado.submitButton.disabled = false;
        if (estado.htmlOriginal !== null) {
            estado.submitButton.innerHTML = estado.htmlOriginal;
        }
    }

    if (estado.closeButton) estado.closeButton.disabled = false;
    estado.fecharButtons.forEach((btn) => { btn.disabled = false; });
}

/**
 * Liga o feedback automático (sem restaurar depois — ver comentário acima)
 * em todo <form> dentro de um .modal da página, exceto o de Atendimentos.
 */
export function setupModalSubmitFeedback() {
    document.querySelectorAll('.modal form').forEach((form) => {
        if (form.id === 'form_atendimento') return;

        form.addEventListener('submit', () => {
            iniciarFeedbackSalvamento(form);
        });
    });
}

export default setupModalSubmitFeedback;
