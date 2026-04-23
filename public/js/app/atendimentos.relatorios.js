// atendimentos.relatorios.js

$(document).ready(function () {
    try {
        configDataTableAtendimentosRelatorios();
    } catch (e) {
        console.error("Erro ao inicializar DataTable:", e);
    }

    initSubmitRelatorio();
    initAtualizarRelatorio();

    $("#btnNovoRelatorio").on("click", function () {
        $("#rel_aten_id").val("");
        $("#rel_aten_label").val("");
        $("#rel_data").val("");

        $("#modal_relatorio").modal({
            backdrop: "static",
            keyboard: false
        });

        setTimeout(() => $("#rel_aten_label").focus(), 200);
    });

    if ($.ui && $.ui.autocomplete && $('#rel_aten_label').length) {
        setupAutocomplete(
            "#rel_aten_label",
            "#rel_aten_id",
            baseURL + "/atendimentos-relatorios/autocomplete"
        );
    }

    $("#modal_relatorio").on("hidden.bs.modal", function () {
        $("#form_relatorio button[type='submit']").prop("disabled", false);
    });

    const relatorioId = $('#btnAtualizarRelatorio').data('relatorio-id');

    if (relatorioId) {
        getData(relatorioId, 'tab-dados');
        getData(relatorioId, 'tab-horarios');
        getData(relatorioId, 'tab-clima');
    }
});

function configDataTableAtendimentosRelatorios() {
    const tableEl = $("#dataTableAtendimentosRelatorios");
    if (!tableEl.length) return;

    if ($.fn.DataTable.isDataTable(tableEl)) {
        return tableEl.DataTable();
    }

    const url = tableEl.data("url");

    return tableEl.DataTable({
        ajax: {
            url: url,
            type: "GET",
            dataSrc: "data",
            error: function (xhr) {
                console.error(
                    "DataTables AJAX error:",
                    xhr.status,
                    xhr.responseText
                );

                showNotification(
                    "fas fa-bug",
                    "Erro ao carregar dados. Código: " + xhr.status,
                    "danger",
                    5000
                );
            }
        },
        columns: [
            { data: "acoes" },
            { data: "data" },
            { data: "obra" },
            { data: "natureza" },
            { data: "setor" },
            { data: "status" }
        ],
        columnDefs: [{ width: "5%", targets: 0 }],
        createdRow: function (row) {
            $("td", row).eq(0).addClass("text-center");
        }
    });
}

function initSubmitRelatorio() {
    $(document)
        .off("submit", "#form_relatorio")
        .on("submit", "#form_relatorio", function (event) {
            event.preventDefault();

            const form = $(this);
            const actionUrl = baseURL + "/atendimentos-relatorios";
            const formData = form.serialize();

            const btnSubmit = form.find("button[type='submit']");
            btnSubmit.prop("disabled", true);

            $.ajax({
                url: actionUrl,
                type: "POST",
                data: formData,
                dataType: "json",
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
                },
                success: function (response) {
                    $("#modal_relatorio").modal("hide");

                    $("#dataTableAtendimentosRelatorios")
                        .DataTable()
                        .ajax.reload(null, false);

                    showNotification(
                        "fas fa-check-double",
                        response.message,
                        "success",
                        2000
                    );

                    btnSubmit.prop("disabled", false);
                },
                error: function (xhr) {
                    btnSubmit.prop("disabled", false);
                    handleAjaxError(xhr);
                }
            });
        });
}

function initAtualizarRelatorio() {
    $(document).off('click', '#btnAtualizarRelatorio').on('click', '#btnAtualizarRelatorio', function () {

        const abaAtiva = $('.tab-pane.active.show').attr('id');
        let form = null;

        switch (abaAtiva) {
            case 'tab-dados':
                form = $('#form_relatorio_dados');
                break;

            case 'tab-horarios':
                form = $('#form_relatorio_horarios');
                break;

            case 'tab-clima':
                form = $('#form_relatorio_clima');
                break;

            default:
                showNotification(
                    'fas fa-exclamation-triangle',
                    'Nenhuma ação definida para esta aba.',
                    'warning',
                    3000
                );
                return;
        }

        if (!form || !form.length) {
            showNotification(
                'fas fa-exclamation-triangle',
                'Formulário não encontrado para esta aba.',
                'warning',
                3000
            );
            return;
        }

        const actionUrl = form.data('action');
        const formData = form.serialize();

        $.ajax({
            url: actionUrl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                showNotification(
                    'fas fa-check',
                    response.message,
                    'success',
                    2000
                );

                const abaId = $('.tab-pane.active.show').attr('id');
                const relatorioId = $('#form_relatorio_dados').data('action').match(/\d+/)[0];

                getData(relatorioId, abaId);
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};
                    let msg = '<ul>';

                    Object.values(errors).flat().forEach(function (m) {
                        msg += `<li>${m}</li>`;
                    });

                    msg += '</ul>';

                    showNotification(
                        'fas fa-bug',
                        'Ocorreram erros:<br>' + msg,
                        'danger',
                        5000
                    );
                } else {
                    showNotification(
                        'fas fa-bug',
                        'Erro ao salvar os dados.',
                        'danger',
                        5000
                    );
                }
            }
        });
    });
}

function handleAjaxError(xhr) {
    if (xhr.status === 422) {
        const errors = xhr.responseJSON?.errors || {};
        let msg = "<ul>";

        Object.values(errors).flat().forEach(m => {
            msg += `<li>${m}</li>`;
        });

        msg += "</ul>";

        showNotification(
            "fas fa-bug",
            "Ocorreram erros:<br>" + msg,
            "danger",
            5000
        );
    } else {
        showNotification(
            "fas fa-bug",
            "Ops... um erro inesperado ocorreu!",
            "danger",
            5000
        );
    }
}

function getData(relatorioId, guia) {
    $.get(`/atendimentos-relatorios/${relatorioId}/get-data`, function (data) {

        switch (guia) {
            case 'tab-dados':
                applyDados(data.dados);
                break;

            case 'tab-horarios':
                applyHorarios(data.horarios);
                break;

            case 'tab-clima':
                applyClima(data.clima);
                break;
        }
    });
}

function applyDados(dados) {
    $('input[name="aten_rel_data"]').val(dados.aten_rel_data_iso);
    $('#tab-dados .dia-semana').text(dados.dia_semana);
    $('#tab-dados .prazo-total').text(dados.prazo_total + ' dias');
    $('#tab-dados .prazo-decorrido').text(dados.prazo_decorrido + ' dias');
    $('#tab-dados .prazo-vencer').text(dados.prazo_vencer + ' dias');
}

function applyHorarios(h) {
    $('input[name="aten_rel_hora_entrada"]').val(h.entrada?.substring(0, 5) || '');
    $('input[name="aten_rel_hora_inicio_intervalo"]').val(h.inicio_intervalo?.substring(0, 5) || '');
    $('input[name="aten_rel_hora_fim_intervalo"]').val(h.fim_intervalo?.substring(0, 5) || '');
    $('input[name="aten_rel_hora_saida"]').val(h.saida?.substring(0, 5) || '');
}

function applyClima(clima) {
    if (clima?.manha) {
        $(`#manha_${clima.manha}`).prop('checked', true);
    }
    if (clima?.tarde) {
        $(`#tarde_${clima.tarde}`).prop('checked', true);
    }
    if (clima?.noite) {
        $(`#noite_${clima.noite}`).prop('checked', true);
    }
}