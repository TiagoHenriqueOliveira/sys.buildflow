// utils.js

// Configuração global do DataTables
if ($.fn.dataTable) {
    $.extend(true, $.fn.dataTable.defaults, {
        language: {
            url: "/js/datatables/pt-BR.json"
        },
        destroy: true,
        ordering: false,
        responsive: true,
        pageLength: 25,
        processing: true
    });
}