import collapse from '@alpinejs/collapse';
import { initTheme, applyTheme } from './theme';
import { sidebarState } from './sidebar';

/**
 * Registra os componentes Alpine.js do SB Admin (sidebar, dark mode).
 * Chame a partir do resources/js/app.js do seu projeto:
 *
 *   import Alpine from 'alpinejs';
 *   import { registerSbAdmin } from './sbadmin/app';
 *
 *   registerSbAdmin(Alpine);
 *   window.Alpine = Alpine;
 *   Alpine.start();
 */
export function registerSbAdmin(Alpine) {
    Alpine.plugin(collapse);

    Alpine.data('sbAdmin', () => ({
        ...sidebarState(),
        darkMode: false,

        init() {
            this.initSidebar();
            this.darkMode = initTheme();
        },

        toggleTheme() {
            this.darkMode = !this.darkMode;
            applyTheme(this.darkMode ? 'dark' : 'light');
        },
    }));
}

export default registerSbAdmin;
