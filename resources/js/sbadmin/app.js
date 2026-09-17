import collapse from '@alpinejs/collapse';
import { sidebarState } from './sidebar';

/**
 * Registra os componentes Alpine.js do SB Admin (sidebar, notificacoes).
 * Chame a partir do resources/js/app.js do seu projeto:
 *
 *   import Alpine from 'alpinejs';
 *   import { registerSbAdmin } from './sbadmin/app';
 *
 *   registerSbAdmin(Alpine);
 *   window.Alpine = Alpine;
 *   Alpine.start();
 *
 * Sem alternancia de tema claro/escuro (dark mode e o unico tema suportado -
 * data-theme/data-bs-theme ja saem fixos em "dark" no script anti-flash do
 * <head>, ver components/layout.blade.php).
 */
export function registerSbAdmin(Alpine) {
    Alpine.plugin(collapse);

    Alpine.data('sbAdmin', () => ({
        ...sidebarState(),

        // Pedido do cliente (2026-09-16): sino de notificacoes no topbar
        // (Sistema de Notificacoes, App\Models\Notificacao no backend).
        // Poll simples (sem websocket/infra de tempo real no projeto) a
        // cada 60s, mais uma carga inicial no init().
        notificacoes: [],
        notificacoesNaoLidas: 0,

        init() {
            this.initSidebar();
            this.carregarNotificacoes();
            setInterval(() => this.carregarNotificacoes(), 60000);
        },

        carregarNotificacoes() {
            fetch('/notificacoes', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then((r) => r.json())
                .then((data) => {
                    this.notificacoes = data.notificacoes;
                    this.notificacoesNaoLidas = data.nao_lidas;
                })
                .catch(() => {});
        },

        marcarNotificacaoLida(notificacao) {
            if (notificacao.lida) return;
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            fetch(`/notificacoes/${notificacao.id}/marcar-lida`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },
            })
                .then(() => {
                    notificacao.lida = true;
                    this.notificacoesNaoLidas = Math.max(0, this.notificacoesNaoLidas - 1);
                })
                .catch(() => {});
        },

        // Pedido do cliente (2026-09-17): alternar lida/nao-lida pelo botao
        // dedicado do item (nao navega, so muda o estado).
        alternarLeituraNotificacao(notificacao) {
            const marcarComoLida = !notificacao.lida;
            const rota = marcarComoLida ? 'marcar-lida' : 'marcar-nao-lida';
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            fetch(`/notificacoes/${notificacao.id}/${rota}`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },
            })
                .then(() => {
                    notificacao.lida = marcarComoLida;
                    this.notificacoesNaoLidas = Math.max(0, this.notificacoesNaoLidas + (marcarComoLida ? -1 : 1));
                })
                .catch(() => {});
        },
    }));
}

export default registerSbAdmin;