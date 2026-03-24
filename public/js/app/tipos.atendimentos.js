// tipos.atendimentos.js

$(document).ready(function () {
    configDataTableTiposAtendimentos();
    initSubmitTipoAtendimento();

    $("#btnNovoTipoAtendimento").on("click", function () {
        abrirModalTipoAtendimento({
            tp_aten_id: "",
            tp_aten_nat_atendimento_id: "",
            tp_aten_descricao: "",
            tp_aten_ativo: 1
        });
    });
});

// Configuração da tabela
function configDataTableTiposAtendimentos() {
    const tableEl = $("#dataTableTiposAtendimentos");
    const url = tableEl.data("url");

    tableEl.DataTable({
        ajax: {
            url: url,
            type: "GET",
            dataSrc: "data"
        },
        columns: [
            { data: "acoes" },
            { data: "nat_aten_descricao" },
            { data: "tp_aten_descricao" },
            { data: "status" }
        ],
        columnDefs: [
            { width: "10%", targets: 0 },
            { width: "30%", targets: 1 },
            { width: "40%", targets: 2 },
            { width: "20%", targets: 3 }
        ],
        createdRow: function (row) {
            $("td", row).eq(0).addClass("text-center");
        },
        fnRowCallback: function (nRow, aData) {
            if (aData.tp_aten_ativo === 0) {
                $("td", nRow).addClass("alert-danger");
            }
        }
    });
}

// Clique no botão editar
$(document).on("click", ".btn-modal-tipo-atendimento", function () {
    abrirModalTipoAtendimento({
        tp_aten_id: $(this).data("id"),
        tp_aten_nat_atendimento_id: $(this).data("nat-id"),
        tp_aten_descricao: $(this).data("descricao"),
        tp_aten_ativo: $(this).data("ativo")
    });
});

// Ao fechar modal
$("#modal_tipo_atendimento").on("hidden.bs.modal", function () {
    $("#form_tipo_atendimento button[type='submit']").prop("disabled", false);
});

// Abre modal e configura campos
function abrirModalTipoAtendimento(data) {
    $("#form_tipo_atendimento button[type='submit']").prop("disabled", false);

    $("#modal_tipo_atendimento").modal({
        backdrop: "static",
        focus: true
    });

    $("#tp_aten_id").val(data.tp_aten_id || "");
    $("#tp_aten_descricao").val(data.tp_aten_descricao || "");
    $("#tp_aten_nat_atendimento_id").val(data.tp_aten_nat_atendimento_id || "");

    const ativo = (data.tp_aten_ativo === 1 || data.tp_aten_ativo === true || data.tp_aten_ativo === "1");
    $("#tp_aten_ativo").prop("checked", ativo);
    $("#tp_aten_ativo_label").text(ativo ? "Ativo" : "Desativado");

    $("#modal_tipo_atendimento").off("shown.bs.modal").on("shown.bs.modal", function () {
        $("#form_tipo_atendimento button[type='submit']").prop("disabled", false);

        if ($("#tp_aten_id").val() === "") {
            $("#modal_tipo_atendimento_label").text("Tipos de Atendimentos | Novo");

            $("#tp_aten_method").val("POST");
            $("#form_tipo_atendimento").attr("action", baseURL + "/tipos-de-atendimentos");

            $("#div_tp_aten_ativo").addClass("d-none");

            $("#tp_aten_ativo").prop("checked", true);
            $("#tp_aten_ativo_label").text("Ativo");
        } else {
            $("#modal_tipo_atendimento_label").text("Tipos de Atendimentos | Editar");

            $("#tp_aten_method").val("PUT");
            $("#form_tipo_atendimento").attr(
                "action",
                baseURL + "/tipos-de-atendimentos/" + $("#tp_aten_id").val()
            );

            $("#div_tp_aten_ativo").removeClass("d-none");
        }

        $("#tp_aten_descricao").focus();
    });

    // Troca label ao clicar
    $("#tp_aten_ativo").off("change").on("change", function () {
        $("#tp_aten_ativo_label").text(this.checked ? "Ativo" : "Desativado");
    });
}

// Submit do form
function initSubmitTipoAtendimento() {
    $(document).off("submit", "#form_tipo_atendimento").on("submit", "#form_tipo_atendimento", function (event) {
        event.preventDefault();

        let form = $(this);
        let actionUrl = form.attr("action");

        let formData = form.serializeArray();
        const ativoMarcado = $("#tp_aten_ativo").is(":checked");
        formData.push({ name: "tp_aten_ativo", value: ativoMarcado ? 1 : 0 });

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
                $("#modal_tipo_atendimento").modal("hide");
                $("#dataTableTiposAtendimentos").DataTable().ajax.reload(null, false);

                showNotification("fas fa-check-double", response.message, "success", 2000);

                form[0].reset();
                $("#tp_aten_id").val("");
                $("#tp_aten_method").val("POST");

                $("#tp_aten_ativo").prop("checked", true);
                $("#tp_aten_ativo_label").text("Ativo");
                $("#div_tp_aten_ativo").addClass("d-none");

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