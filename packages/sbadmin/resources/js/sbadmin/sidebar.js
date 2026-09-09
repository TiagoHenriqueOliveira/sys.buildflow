const COLLAPSE_STORAGE_KEY = 'sbadmin-sidebar-collapsed';

// Estado da sidebar (colapsar no desktop / off-canvas no mobile). O
// accordion do submenu (2o nivel) e os dropdowns da topbar usam x-data
// local dentro dos proprios componentes Blade, sem precisar deste modulo.
export function sidebarState() {
    return {
        collapsed: localStorage.getItem(COLLAPSE_STORAGE_KEY) === 'true',
        mobileOpen: false,
        // Propriedade reativa espelhando window.innerWidth >= 992 (breakpoint
        // lg do Bootstrap). Usada nos expressoes Alpine (ex.: flyout do
        // submenu quando a sidebar esta recolhida) em vez de window.innerWidth
        // diretamente - referenciar o global dentro de uma expressao reativa
        // do Alpine impede o rastreamento correto de outras dependencias
        // (ex.: "collapsed") na mesma expressao.
        isDesktop: window.innerWidth >= 992,

        initSidebar() {
            this.$watch('collapsed', (value) => {
                localStorage.setItem(COLLAPSE_STORAGE_KEY, value ? 'true' : 'false');
            });

            window.addEventListener('resize', () => {
                this.isDesktop = window.innerWidth >= 992;

                if (this.isDesktop && this.mobileOpen) {
                    this.mobileOpen = false;
                }
            });
        },
    };
}
