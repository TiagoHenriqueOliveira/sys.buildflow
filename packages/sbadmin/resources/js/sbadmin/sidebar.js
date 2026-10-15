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

        // Posicao do flyout (submenu com a sidebar recolhida) limitada a
        // janela visivel. Antes o painel abria sempre alinhado ao topo do
        // botao clicado - para o ultimo item do rail ("Configuracoes"), com a
        // escala do Windows em 125%+ (menos altura util em pixels CSS), metade
        // do painel ficava abaixo da borda da janela, inalcancavel (e
        // position: fixed, rolar a pagina nao traz de volta). Chamado depois do
        // painel renderizar, pra medir o tamanho real. A margem de 16px casa
        // com o max-height: calc(100vh - 2rem) de .sbadmin-submenu--flyout -
        // painel maior que a janela fica em top: 16px e rola por dentro.
        posicionarFlyout(botao, painel) {
            const margem = 16;
            const r = botao.getBoundingClientRect();
            let top = r.top;
            let left = r.right + 8;

            const limiteInferior = window.innerHeight - margem;
            if (top + painel.offsetHeight > limiteInferior) {
                top = Math.max(margem, limiteInferior - painel.offsetHeight);
            }

            const limiteDireito = window.innerWidth - margem;
            if (left + painel.offsetWidth > limiteDireito) {
                left = Math.max(margem, limiteDireito - painel.offsetWidth);
            }

            return { top: top + 'px', left: left + 'px' };
        },
    };
}
