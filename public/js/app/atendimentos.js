// atendimentos.js

$(document).ready(function () {
    try {
        configDataTableAtendimentos();
    } catch (e) {
        console.error("Erro ao inicializar DataTable:", e);
    }

    initSubmitAtendimento();

    $("#btnNovoAtendimento").on("click", function () {
        abrirModalAtendimento({
            aten_id: "",
            aten_tp_atendimento_id: "",
            aten_natureza_id: "",
            aten_cliente_id: "",
            aten_cliente_nome: "",
            aten_usuario_id: "",
            aten_status: 0,
            aten_nr_proposta: "",
            aten_descricao: "",
            aten_responsavel: "",
            aten_endereco: "",
            aten_dt_inicio: "",
            aten_dt_fim: ""
        });
    });

    setupAutocomplete("#aten_cliente_nome", "#aten_cliente_id", baseURL + "/atendimentos/autocomplete");

    $(document).on("change", "#aten_tp_atendimento_id", function () {
        const tipoId = $(this).val();

        if (!$("#aten_id").val()) {
            atualizarSelectNaturezas(tipoId, null, false);
        }

        syncHiddenFromSelects();
    });

    $(document).on("change", "#aten_nat_atendimento_id, #aten_natureza_id, #aten_natureza_select", function () {
        syncHiddenFromSelects();
    });

    $("#modal_atendimento").on("hidden.bs.modal", function () {
        $("#form_atendimento button[type='submit']").prop("disabled", false);
    });
});

function configDataTableAtendimentos() {
    const tableEl = $("#dataTableAtendimentos");
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
                console.error("DataTables AJAX error:", xhr.status, xhr.responseText);
                showNotification("fas fa-bug", "Erro ao carregar dados. Código: " + xhr.status, "danger", 5000);
            }
        },
        columns: [
            { data: "acoes" },
            { data: "tipo" },
            { data: "natureza" },
            { data: "usuario" },
            { data: "cliente" },
            { data: "obra" },
            { data: "periodo" },
            { data: "status" }
        ],
        columnDefs: [{ width: "5%", targets: 0 }],
        createdRow: function (row) {
            $("td", row).eq(0).addClass("text-center");
        }
    });
}

$(document).on("click", ".btn-modal-atendimento", function () {
    abrirModalAtendimento({
        aten_id: $(this).data("id"),
        aten_tp_atendimento_id: $(this).data("tp"),
        aten_natureza_id: $(this).data("natureza-id"),
        aten_cliente_id: $(this).data("cliente-id"),
        aten_cliente_nome: $(this).data("cliente"),
        aten_usuario_id: $(this).data("usuario-id"),
        aten_status: $(this).data("status"),
        aten_nr_proposta: $(this).data("nr-proposta"),
        aten_descricao: $(this).data("obra"),
        aten_responsavel: $(this).data("responsavel"),
        aten_endereco: $(this).data("endereco"),
        aten_dt_inicio: $(this).data("dt-inicio"),
        aten_dt_fim: $(this).data("dt-fim")
    });
});

function getNaturezaSelect() {
    let s = $("#aten_nat_atendimento_id");
    if (s.length) return s;

    s = $("#aten_natureza_id");
    if (s.length) return s;

    s = $("#aten_natureza_select");
    if (s.length) return s;

    return $();
}

function getTipoSelect() {
    return $("#aten_tp_atendimento_id");
}

function ensureHiddenInputs() {
    const form = $("#form_atendimento");
    if (!form.length) return;

    if (!$("#aten_natureza_id_hidden").length) {
        form.append('<input type="hidden" name="aten_natureza_id" id="aten_natureza_id_hidden">');
    }
}

function syncHiddenFromSelects() {
    ensureHiddenInputs();

    const naturezaSelect = getNaturezaSelect();
    const naturezaVal = naturezaSelect.length ? (naturezaSelect.val() || "") : "";

    $("#aten_natureza_id_hidden").val(naturezaVal);
}

function atualizarSelectNaturezas(tipoId, selectedNaturezaId, lockAfter) {
    const select = getNaturezaSelect();

    if (!select.length) {
        console.warn("Select de natureza não encontrado (#aten_nat_atendimento_id / #aten_natureza_id / #aten_natureza_select).");
        return;
    }

    select.prop("disabled", true).html('<option value="" selected disabled hidden>Selecione...</option>');

    syncHiddenFromSelects();

    if (!tipoId) return;

    $.ajax({
        url: baseURL + "/atendimentos/naturezas-por-tipo",
        type: "GET",
        dataType: "json",
        data: { tipo_id: tipoId },
        success: function (data) {
            if (data && !Array.isArray(data)) data = [data];

            if (Array.isArray(data) && data.length) {
                data.forEach(n => {
                    select.append(`<option value="${n.id}">${n.label}</option>`);
                });

                select.prop("disabled", false);

                if (selectedNaturezaId !== null && selectedNaturezaId !== undefined && String(selectedNaturezaId) !== "") {
                    select.val(String(selectedNaturezaId));
                }

                syncHiddenFromSelects();

                if (lockAfter) {
                    select.prop("disabled", true);
                }
            } else {
                select.prop("disabled", true).html('<option value="" selected disabled hidden>Nenhuma natureza vinculada</option>');

                syncHiddenFromSelects();
            }
        },
        error: function (xhr) {
            console.error("Erro ao carregar naturezas:", xhr.status, xhr.responseText);
            select.prop("disabled", true).html('<option value="" selected disabled hidden>Erro ao carregar naturezas</option>');

            syncHiddenFromSelects();
        }
    });
}

function abrirModalAtendimento(data) {
    $("#form_atendimento button[type='submit']").prop("disabled", false);
    $("#modal_atendimento").modal({ backdrop: "static", focus: true });

    const tipoSelect = getTipoSelect();
    const naturezaSelect = getNaturezaSelect();

    $("#aten_id").val(data.aten_id || "");

    $("#aten_cliente_id").val(data.aten_cliente_id || "");
    $("#aten_cliente_nome").val(data.aten_cliente_nome || "");
    $("#aten_usuario_id").val(data.aten_usuario_id || "");
    $("#aten_nr_proposta").val(data.aten_nr_proposta || "");
    $("#aten_descricao").val(data.aten_descricao || "");
    $("#aten_responsavel").val(data.aten_responsavel || "");
    $("#aten_endereco").val(data.aten_endereco || "");
    $("#aten_dt_inicio").val(data.aten_dt_inicio || "");
    $("#aten_dt_fim").val(data.aten_dt_fim || "");

    $("input[name='aten_status']").prop("checked", false);
    $(`#aten_status_${data.aten_status ?? 0}`).prop("checked", true);

    $("#modal_atendimento").off("shown.bs.modal").on("shown.bs.modal", function () {
        const isEdit = !!data.aten_id;

        if (!isEdit) {
            $("#modal_atendimento_label").text("Atendimentos | Novo");
            $("#aten_method").val("POST");
            $("#form_atendimento").attr("action", baseURL + "/atendimentos");

            tipoSelect.prop("disabled", false).val("").trigger("change");

            if (naturezaSelect.length) {
                naturezaSelect.prop("disabled", true).html('<option value="" selected disabled hidden>Selecione...</option>');
            }

            syncHiddenFromSelects();

        } else {
            $("#modal_atendimento_label").text("Atendimentos | Editar");
            $("#aten_method").val("PUT");
            $("#form_atendimento").attr("action", baseURL + "/atendimentos/" + data.aten_id);

            tipoSelect.val(String(data.aten_tp_atendimento_id)).prop("disabled", true);

            atualizarSelectNaturezas(
                data.aten_tp_atendimento_id,
                data.aten_natureza_id,
                true
            );
        }
    });
}

function initSubmitAtendimento() {
    $(document).off("submit", "#form_atendimento").on("submit", "#form_atendimento", function (event) {
        event.preventDefault();

        syncHiddenFromSelects();

        const form = $(this);
        const actionUrl = form.attr("action");
        const formData = form.serializeArray();

        const btnSubmit = form.find("button[type='submit']");
        btnSubmit.prop("disabled", true);

        $.ajax({
            url: actionUrl,
            type: "POST",
            data: $.param(formData),
            dataType: "json",
            headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
            success: function (response) {
                $("#modal_atendimento").modal("hide");
                $("#dataTableAtendimentos").DataTable().ajax.reload(null, false);
                showNotification("fas fa-check-double", response.message, "success", 2000);
                btnSubmit.prop("disabled", false);
            },
            error: function (xhr) {
                btnSubmit.prop("disabled", false);

                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};
                    let msg = "<ul>";

                    $.each(errors, function (k, v) {
                        (v || []).forEach(m => msg += `<li>${m}</li>`);
                    });

                    msg += "</ul>";

                    showNotification(
                        "fas fa-bug",
                        "Ocorreram erros ao salvar:<br>" + msg,
                        "danger",
                        5000
                    );
                } else {
                    console.error("Erro submit:", xhr.status, xhr.responseText);
                    showNotification(
                        "fas fa-bug",
                        "Ops... um erro inesperado ocorreu! Código: " + xhr.status,
                        "danger",
                        5000
                    );
                }
            }
        });
    });
}