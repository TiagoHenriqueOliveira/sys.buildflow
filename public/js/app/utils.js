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

// Função para exibir notificações na tela
function showNotification(icon, message, type, delay) {
    $.notify({
        icon: icon,
        message: message
    }, {
        type: type,
        delay: delay,
        z_index: 9999,
        placement: {
            from: "top",
            align: "center"
        }
    });
}