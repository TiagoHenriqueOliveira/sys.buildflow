/**
 * Store Alpine.js que alimenta o <x-sbadmin::confirm-modal /> montado uma
 * unica vez no layout base (ver packages/sbadmin/resources/views/components
 * /confirm-modal.blade.php) — substitui o confirm() nativo do navegador em
 * qualquer acao destrutiva/irreversivel (excluir, sair do sistema, etc).
 *
 * Uso a partir de qualquer Blade/JS do projeto:
 *
 *   confirmar('Deseja encerrar sua sessão?', {
 *       rotulo: 'Sair',
 *       onConfirm: () => form.submit(),
 *   });
 */
export function registerConfirmModal(Alpine) {
    Alpine.store('confirmacao', {
        aberto: false,
        mensagem: '',
        tipoConfirmar: 'primary',
        rotuloConfirmar: 'Confirmar',
        _onConfirm: null,

        abrir(mensagem, opcoes = {}) {
            this.mensagem = mensagem;
            this.tipoConfirmar = opcoes.tipo || 'primary';
            this.rotuloConfirmar = opcoes.rotulo || 'Confirmar';
            this._onConfirm = typeof opcoes.onConfirm === 'function' ? opcoes.onConfirm : null;
            this.aberto = true;
        },

        confirmar() {
            this.aberto = false;
            const callback = this._onConfirm;
            this._onConfirm = null;
            if (callback) callback();
        },

        cancelar() {
            this.aberto = false;
            this._onConfirm = null;
        },
    });

    window.confirmar = (mensagem, opcoes) => Alpine.store('confirmacao').abrir(mensagem, opcoes);
}

export default registerConfirmModal;
