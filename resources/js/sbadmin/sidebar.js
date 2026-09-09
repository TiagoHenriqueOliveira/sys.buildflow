// Estado da sidebar (colapsar no desktop / off-canvas no mobile). O
// accordion do submenu (2o nivel) e os dropdowns da topbar usam x-data
// local dentro dos proprios componentes Blade, sem precisar deste modulo.
//
// "collapsed" e permanentemente true (decisao do produto: sidebar sempre
// recolhida no desktop, sem toggle de expandir - ver topbar.blade.php, que
// nao tem mais o botao que alterava esse valor). O rail de icones em si e
// puro CSS (media query em _layout.scss, nao depende mais de "collapsed"),
// mas a propriedade continua existindo porque o flyout do submenu em
// sidebar-item.blade.php ainda referencia "collapsed && isDesktop"
// reativamente pra decidir flyout vs. accordion inline e calcular a posicao
// do painel; so nao existe mais nenhum caminho de codigo que mude o valor.
export function sidebarState() {
    return {
        collapsed: true,
        mobileOpen: false,
        // Propriedade reativa espelhando window.innerWidth >= 992 (breakpoint
        // lg do Bootstrap). Usada nos expressoes Alpine (ex.: flyout do
        // submenu quando a sidebar esta recolhida) em vez de window.innerWidth
        // diretamente - referenciar o global dentro de uma expressao reativa
        // do Alpine impede o rastreamento correto de outras dependencias
        // (ex.: "collapsed") na mesma expressao.
        isDesktop: window.innerWidth >= 992,

        initSidebar() {
            window.addEventListener('resize', () => {
                this.isDesktop = window.innerWidth >= 992;

                if (this.isDesktop && this.mobileOpen) {
                    this.mobileOpen = false;
                }
            });
        },
    };
}
