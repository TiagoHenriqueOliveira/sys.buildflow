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

/**
 * Configura autocomplete para um campo de texto
 * @param {string} inputSelector - Seletor do campo de input (ex: '#crg_cbo_descricao')
 * @param {string} hiddenInputSelector - Seletor do campo hidden para armazenar o ID (ex: '#crg_cbo_id')
 * @param {string} url - URL da API que retorna os dados para autocomplete
 * @param {function} [callback] - Função de callback opcional a ser executada após seleção
 */
function setupAutocomplete(inputSelector, hiddenInputSelector, url, callback) {
    $(inputSelector).autocomplete({
        source: function (request, response) {
            $.ajax({
                url: url,
                dataType: "json",
                data: {
                    term: request.term
                },
                success: function (data) {
                    response(data);
                },
                error: function (xhr, status, error) {
                    console.error("Erro no autocomplete:", error);
                }
            });
        },
        minLength: 3,
        select: function (event, ui) {
            $(inputSelector).val(ui.item.label);
            $(hiddenInputSelector).val(ui.item.id);
            if (typeof callback === 'function') {
                callback(ui.item);
            }
            return false;
        },
        change: function (event, ui) {
            if (!ui.item) {
                $(hiddenInputSelector).val('');
            }
        }
    }).autocomplete("instance")._renderItem = function (ul, item) {
        return $("<li>").append(`<div>${item.label}</div>`).appendTo(ul);
    };
}