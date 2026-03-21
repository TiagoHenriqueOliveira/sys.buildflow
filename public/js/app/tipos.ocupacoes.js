// tipos.ocupacoes.js
$(document).ready(function () {
    configDataTableTiposOcupacoes();
    initSubmitTipoOcupacao();
    
    $("#btnNovoTipoOcupacao").on("click", function () {
        abrirModalTipoOcupacao({
            tp_ocup_id: "",
            tp_ocup_descricao: "",
            tp_ocup_ativo: 1
        });
    });
});

// Configuração da tabela
function configDataTableTiposOcupacoes() {
    const tableEl = $("#dataTableTiposOcupacoes");
    const url = tableEl.data("url");

    tableEl.DataTable({
        ajax: {
            url: url,
            type: "GET",
            dataSrc: "data"
        },
        columns: [
            { data: "acoes" },
            { data: "tp_ocup_descricao" },
            { data: "status" }
        ],
        columnDefs: [
            { width: "10%", targets: 0 },
            { width: "60%", targets: 1 },
            { width: "30%", targets: 2 }
        ],
        createdRow: function (row) {
            $("td", row).eq(0).addClass("text-center");
        },
        fnRowCallback: function (nRow, aData) {
            if (aData.tp_ocup_ativo === 0) {
                $("td", nRow).addClass("alert-danger");
            }
        }
    });
}

// Clique no botão editar (gerado na coluna ações)
$(document).on("click", ".btn-modal-tipo-ocupacao", function () {
    abrirModalTipoOcupacao({
        tp_ocup_id: $(this).data("id"),
        tp_ocup_descricao: $(this).data("descricao"),
        tp_ocup_ativo: $(this).data("ativo")
    });
});

// Abre modal e configura campos
function abrirModalTipoOcupacao(data) {
    $("#form_tipo_ocupacao button[type='submit']").prop("disabled", false);

    $("#modal_tipo_ocupacao").modal({
        backdrop: "static",
        focus: true
    });

    $("#tp_ocup_id").val(data.tp_ocup_id || "");
    $("#tp_ocup_descricao").val(data.tp_ocup_descricao || "");

    const ativo = (data.tp_ocup_ativo === 1 || data.tp_ocup_ativo === true || data.tp_ocup_ativo === "1");
    $("#tp_ocup_ativo").prop("checked", ativo);
    $("#tp_ocup_ativo_label").text(ativo ? "Ativo" : "Desativado");

    $("#modal_tipo_ocupacao").off("shown.bs.modal").on("shown.bs.modal", function () {
        $("#form_tipo_ocupacao button[type='submit']").prop("disabled", false);

        if ($("#tp_ocup_id").val() === "") {
            $("#modal_tipo_ocupacao_label").text("Tipo de Ocupação | Novo");

            $("#tp_ocup_method").val("POST");
            $("#form_tipo_ocupacao").attr("action", baseURL + "/tipos-de-mao-de-obra");

            $("#div_tp_ocup_ativo").addClass("d-none");

            $("#tp_ocup_ativo").prop("checked", true);
            $("#tp_ocup_ativo_label").text("Ativo");
        } else {
            $("#modal_tipo_ocupacao_label").text("Tipo de Ocupação | Editar");

            $("#tp_ocup_method").val("PUT");
            $("#form_tipo_ocupacao").attr(
                "action",
                baseURL + "/tipos-de-mao-de-obra/" + $("#tp_ocup_id").val()
            );

            $("#div_tp_ocup_ativo").removeClass("d-none");
        }

        $("#tp_ocup_descricao").focus();
    });

    // Troca label ao clicar
    $("#tp_ocup_ativo").off("change").on("change", function () {
        $("#tp_ocup_ativo_label").text(this.checked ? "Ativo" : "Desativado");
    });
}

// Submit do form (store/update)
function initSubmitTipoOcupacao() {
    $(document).off("submit", "#form_tipo_ocupacao").on("submit", "#form_tipo_ocupacao", function (event) {
        event.preventDefault();

        let form = $(this);
        let actionUrl = form.attr("action");

        let formData = form.serializeArray();
        const ativoMarcado = $("#tp_ocup_ativo").is(":checked");
        formData.push({ name: "tp_ocup_ativo", value: ativoMarcado ? 1 : 0 });

        const btnSubmit = form.find("button[type='submit']");
        btnSubmit.prop("disabled", true);

        $.ajax({
            url: actionUrl,
            type: "POST",
            data: $.param(formData),
            dataType: "json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            },
            success: function (response) {
                $("#modal_tipo_ocupacao").modal("hide");
                $("#dataTableTiposOcupacoes").DataTable().ajax.reload(null, false);

                showNotification(
                    "fas fa-check-double",
                    response.message,
                    "success",
                    2000
                );

                form[0].reset();
                $("#tp_ocup_id").val("");
                $("#tp_ocup_method").val("POST");
                $("#tp_ocup_ativo").prop("checked", true);
                $("#tp_ocup_ativo_label").text("Ativo");

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

                    showNotification(
                        "fas fa-bug",
                        "Ocorreram erros ao salvar o registro:<br>" + errorMessages,
                        "danger",
                        5000
                    );
                } else {
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