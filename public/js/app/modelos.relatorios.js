// modelos.relatorios.js

$(document).ready(function () {
    configDataTableModelosRelatorios();
    initSubmitModeloRelatorio();

    $("#btnNovoModeloRelatorio").on("click", function () {
        abrirModalModeloRelatorio({
            mod_rel_id: "",
            mod_rel_descricao: "",
            mod_rel_tp_data: "0",
            mod_rel_ativo: 1,
            mod_rel_entrega_tecnica: 0,
        });
    });
});

// Configuração da tabela
function configDataTableModelosRelatorios() {
    const tableEl = $("#dataTableModelosRelatorios");
    const url = tableEl.data("url");

    tableEl.DataTable({
        ajax: {
            url: url,
            type: "GET",
            dataSrc: "data"
        },
        serverSide: true,
        processing: true,
        columns: [
            { data: "acoes" },
            { data: "mod_rel_descricao" },
            { data: "tipo_data" },
            { data: "status" }
        ],
        columnDefs: [
            { width: "10%", targets: 0 },
            { width: "45%", targets: 1 },
            { width: "25%", targets: 2 },
            { width: "20%", targets: 3 }
        ],
        createdRow: function (row) {
            $("td", row).eq(0).addClass("text-center");
        },
        fnRowCallback: function (nRow, aData) {
            if (aData.mod_rel_ativo === 0) {
                $("td", nRow).addClass("alert-danger");
            }
        }
    });
}

// Clique no botão editar
$(document).on("click", ".btn-modal-modelo-relatorio", function () {
    abrirModalModeloRelatorio({
        mod_rel_id: $(this).data("id"),
        mod_rel_descricao: $(this).data("descricao"),
        mod_rel_tp_data: $(this).data("tp-data"),
        mod_rel_ativo: $(this).data("ativo"),
        mod_rel_entrega_tecnica: $(this).data("entrega-tecnica"),
    });
});

// Ao fechar modal
$("#modal_modelo_relatorio").on("hidden.bs.modal", function () {
    $("#form_modelo_relatorio button[type='submit']").prop("disabled", false);
});

// Switch Entrega Técnica: badge Sim/Não
$(document).on("change", "#mod_rel_entrega_tecnica", function () {
    atualizarEntregaTecnicaBadge(this.checked);
});

function atualizarEntregaTecnicaBadge(checked) {
    const badge = $("#mod_rel_entrega_tecnica_badge");
    if (checked) {
        badge.text("Sim").removeClass("badge-secondary").addClass("badge-success");
    } else {
        badge.text("Não").removeClass("badge-success").addClass("badge-secondary");
    }
}

// Abre modal e configura campos
function abrirModalModeloRelatorio(data) {
    $("#form_modelo_relatorio button[type='submit']").prop("disabled", false);

    $("#modal_modelo_relatorio").modal({
        backdrop: "static",
        focus: true
    });

    $("#mod_rel_id").val(data.mod_rel_id || "");
    $("#mod_rel_descricao").val(data.mod_rel_descricao || "");

    // radio tipo data
    $("input[name='mod_rel_tp_data']").prop("checked", false);
    if (data.mod_rel_tp_data === 1 || data.mod_rel_tp_data === "1") {
        $("#mod_rel_tp_data_1").prop("checked", true);
    } else {
        $("#mod_rel_tp_data_0").prop("checked", true);
    }

    const entrega = (data.mod_rel_entrega_tecnica === 1 || data.mod_rel_entrega_tecnica === true || data.mod_rel_entrega_tecnica === "1");
    $("#mod_rel_entrega_tecnica").prop("checked", entrega);
    atualizarEntregaTecnicaBadge(entrega);

    // ativo
    const ativo = (data.mod_rel_ativo === 1 || data.mod_rel_ativo === true || data.mod_rel_ativo === "1");
    $("#mod_rel_ativo").prop("checked", ativo);
    $("#mod_rel_ativo_label").text(ativo ? "Ativo" : "Desativado");

    $("#modal_modelo_relatorio").off("shown.bs.modal").on("shown.bs.modal", function () {
        $("#form_modelo_relatorio button[type='submit']").prop("disabled", false);

        if ($("#mod_rel_id").val() === "") {
            $("#modal_modelo_relatorio_label").text("Modelos de Relatórios | Novo");

            $("#mod_rel_method").val("POST");
            $("#form_modelo_relatorio").attr("action", baseURL + "/modelos-de-relatorios");

            $("#div_mod_rel_ativo").addClass("d-none");
            $("#mod_rel_ativo").prop("checked", true);
            $("#mod_rel_ativo_label").text("Ativo");
        } else {
            $("#modal_modelo_relatorio_label").text("Modelos de Relatórios | Editar");

            $("#mod_rel_method").val("PUT");
            $("#form_modelo_relatorio").attr(
                "action",
                baseURL + "/modelos-de-relatorios/" + $("#mod_rel_id").val()
            );

            $("#div_mod_rel_ativo").removeClass("d-none");
        }

        $("#mod_rel_descricao").focus();
    });

    // Troca label ao clicar
    $("#mod_rel_ativo").off("change").on("change", function () {
        $("#mod_rel_ativo_label").text(this.checked ? "Ativo" : "Desativado");
    });
}

// helper checkbox
function setCheckbox(id, value) {
    const checked = (value === 1 || value === true || value === "1");
    $("#" + id).prop("checked", checked);
}

// Submit (store/update)
function initSubmitModeloRelatorio() {
    $(document).off("submit", "#form_modelo_relatorio").on("submit", "#form_modelo_relatorio", function (event) {
        event.preventDefault();

        let form = $(this);
        let actionUrl = form.attr("action");

        let formData = form.serializeArray();

        // radio tipo data (garantia)
        const tpData = $("input[name='mod_rel_tp_data']:checked").val();
        formData.push({ name: "mod_rel_tp_data", value: tpData });

        const switchIds = ["mod_rel_entrega_tecnica"];
        switchIds.forEach(function (id) {
            formData.push({ name: id, value: $("#" + id).is(":checked") ? 1 : 0 });
        });

        // ativo
        formData.push({ name: "mod_rel_ativo", value: $("#mod_rel_ativo").is(":checked") ? 1 : 0 });

        const btnSubmit = form.find("button[type='submit']");
        btnSubmit.prop("disabled", true);

        $.ajax({
            url: actionUrl,
            type: "POST",
            data: $.param(formData),
            dataType: "json",
            headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
            success: function (response) {
                $("#modal_modelo_relatorio").modal("hide");
                $("#dataTableModelosRelatorios").DataTable().ajax.reload(null, false);

                showNotification("fas fa-check-double", response.message, "success", 2000);

                form[0].reset();
                $("#mod_rel_id").val("");
                $("#mod_rel_method").val("POST");

                $("#div_mod_rel_ativo").addClass("d-none");
                atualizarEntregaTecnicaBadge(false);

                btnSubmit.prop("disabled", false);
            },
            error: function (xhr) {
                btnSubmit.prop("disabled", false);

                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessages = "<ul>";

                    $.each(errors, function (key, messages) {
                        $.each(messages, function (index, message) {
                            errorMessages += "<li>" + message + "</li>";
                        });
                    });

                    errorMessages += "</ul>";

                    showNotification("fas fa-bug", "Ocorreram erros ao salvar o registro:<br>" + errorMessages, "danger", 5000);
                } else {
                    showNotification("fas fa-bug", "Ops... um erro inesperado ocorreu! Código: " + xhr.status, "danger", 5000);
                }
            }
        });
    });
}