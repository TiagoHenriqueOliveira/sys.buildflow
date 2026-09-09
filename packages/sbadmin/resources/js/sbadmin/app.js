import collapse from '@alpinejs/collapse';
import { sidebarState } from './sidebar';

/**
 * Registra os componentes Alpine.js do SB Admin (sidebar).
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

        init() {
            this.initSidebar();
        },
    }));
}

export default registerSbAdmin;
