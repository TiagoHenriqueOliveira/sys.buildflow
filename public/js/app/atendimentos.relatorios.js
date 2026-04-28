// atendimentos.relatorios.js

$(document).ready(function () {
    try {
        configDataTableAtendimentosRelatorios();
    } catch (e) {
        console.error("Erro ao inicializar DataTable:", e);
    }

    initSubmitRelatorio();
    initAtualizarRelatorio();
    initMaoObraTab();

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

function initSubmitRelatorio() {
    $(document).off("submit", "#form_relatorio").on("submit", "#form_relatorio", function (event) {
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

function initMaoObraTab() {
    if ($.ui && $.ui.autocomplete && $('#mao_obra_label').length) {
        setupAutocomplete(
            '#mao_obra_label',
            '#mao_obra_id',
            baseURL + '/mao-de-obra/autocomplete',
            function (item) {
                $('#mao_obra_tp_id').val(item.tp_id || '');
                $('#mao_obra_label').data('selectedItem', item);
            }
        );
    }

    $(document).off('input blur', '#mao_obra_qtd').on('input blur', '#mao_obra_qtd', function () {
        const v = parseInt($(this).val(), 10);
        if (isNaN(v) || v < 1) {
            $(this).val(1);
        }
    });

    $(document).off('change', '#mao_obra_label').on('change', '#mao_obra_label', function () {
        if (!$('#mao_obra_id').val()) {
            $('#mao_obra_tp_id').val('');
            $('#mao_obra_label').removeData('selectedItem');
        }
    });

    $(document).off('click', '#btnAddMaoObra').on('click', '#btnAddMaoObra', function () {

        const relatorioId = getRelatorioIdAtual();

        if (!relatorioId) {
            showNotification(
                'fas fa-exclamation-triangle',
                'Salve o relatório antes de adicionar mão de obra.',
                'warning',
                3500
            );
            return;
        }

        const ocupId = $('#mao_obra_id').val();
        const tpId = $('#mao_obra_tp_id').val();
        const qtd = parseInt($('#mao_obra_qtd').val(), 10);

        if (!ocupId || !tpId) {
            showNotification(
                'fas fa-exclamation-triangle',
                'Selecione uma mão de obra válida na lista.',
                'warning',
                3000
            );
            return;
        }

        if (isNaN(qtd) || qtd < 1) {
            showNotification(
                'fas fa-exclamation-triangle',
                'Quantidade deve ser no mínimo 1.',
                'warning',
                3000
            );
            return;
        }

        const key = `${tpId}-${ocupId}`;
        if ($(`#tableMaoObra tbody tr[data-key="${key}"]`).length) {
            showNotification(
                'fas fa-exclamation-triangle',
                'Essa mão de obra já foi adicionada.',
                'warning',
                3000
            );
            return;
        }

        const btn = $('#btnAddMaoObra');
        btn.prop('disabled', true);

        $.ajax({
            url: baseURL + `/atendimentos-relatorios/${relatorioId}/mao-de-obra`,
            type: 'POST',
            dataType: 'json',
            data: {
                ocup_id: ocupId,
                qtd: qtd
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                const d = response.data;

                const tr = $(`
                        <tr data-key="${d.tp_id}-${d.ocup_id}"
                            data-ocup-id="${d.ocup_id}"
                            style="display:none;">
                            <td>
                                <button type="button"
                                    class="btn btn-danger btn-sm btn-icon-split btnRemoveMaoObra">
                                    <span class="icon text-white-50">
                                        <i class="fas fa-trash"></i>
                                    </span>
                                    <span class="text">Excluir</span>
                                </button>
                            </td>
                            <td>${escapeHtml(d.tp_label)}</td>
                            <td>${escapeHtml(d.ocup)}</td>
                            <td>${d.qtd}</td>
                        </tr>
                    `);

                $('#tableMaoObra tbody').append(tr);
                tr.fadeIn(500);

                showNotification(
                    'fas fa-check',
                    response.message,
                    'success',
                    2000
                );

                $('#mao_obra_label').val('').removeData('selectedItem');
                $('#mao_obra_id').val('');
                $('#mao_obra_tp_id').val('');
                $('#mao_obra_qtd').val(1);
                $('#mao_obra_label').focus();
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const msg = xhr.responseJSON?.message || 'Erro de validação.';
                    showNotification('fas fa-bug', msg, 'danger', 4000);
                } else {
                    handleAjaxError(xhr);
                }
            },
            complete: function () {
                btn.prop('disabled', false);
            }
        });
    });

    $(document).off('click', '.btnRemoveMaoObra').on('click', '.btnRemoveMaoObra', function () {

        const relatorioId = getRelatorioIdAtual();
        const tr = $(this).closest('tr');
        const ocupId = tr.data('ocup-id');

        if (!relatorioId || !ocupId) {
            tr.fadeOut(500, function () { tr.remove(); });
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true);

        $.ajax({
            url: baseURL + `/atendimentos-relatorios/${relatorioId}/mao-de-obra/${ocupId}`,
            type: 'DELETE',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                tr.fadeOut(500, function () {
                    tr.remove();
                });

                showNotification(
                    'fas fa-check',
                    response.message,
                    'success',
                    2000
                );
            },
            error: function (xhr) {
                btn.prop('disabled', false);
                handleAjaxError(xhr);
            }
        });
    });
}

function getRelatorioIdAtual() {
    const byData = $('#btnAtualizarRelatorio').data('relatorio-id');
    if (byData) return byData;

    const action = $('#form_relatorio_dados').data('action');
    if (action) {
        const m = String(action).match(/\d+/);
        if (m) return m[0];
    }

    return null;
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

        if (data.mao_obra) {
            applyMaoDeObra(data.mao_obra);
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

function applyMaoDeObra(lista) {
    const tbody = $('#tableMaoObra tbody');

    tbody.empty();

    if (!Array.isArray(lista) || !lista.length) {
        return;
    }

    lista.forEach(function (d) {
        const key = `${d.tp_id}-${d.ocup_id}`;

        const tr = $(`
            <tr data-key="${key}"
                data-ocup-id="${d.ocup_id}"
                style="display:none;">
                <td>
                    <button type="button"
                        class="btn btn-danger btn-sm btn-icon-split btnRemoveMaoObra">
                        <span class="icon text-white-50">
                            <i class="fas fa-trash"></i>
                        </span>
                        <span class="text">Excluir</span>
                    </button>
                </td>
                <td>${escapeHtml(d.tp_label)}</td>
                <td>${escapeHtml(d.ocup)}</td>
                <td>${d.qtd}</td>
            </tr>
        `);

        tbody.append(tr);
        tr.fadeIn(300);
    });
}