// ocorrencias.js

$(document).ready(function () {
    configDataTableOcorrencias();
    initSubmitOcorrencia();

    $("#btnNovaOcorrencia").on("click", function () {
        abrirModalOcorrencia({
            ocor_id: "",
            ocor_descricao: "",
            ocor_ativo: 1
        });
    });
});

// Configuração da tabela
function configDataTableOcorrencias() {
    const tableEl = $("#dataTableOcorrencias");
    const url = tableEl.data("url");

    tableEl.DataTable({
        ajax: {
            url: url,
            type: "GET",
            dataSrc: "data"
        },
        columns: [
            { data: "acoes" },
            { data: "ocor_descricao" },
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
            if (aData.ocor_ativo === 0) {
                $("td", nRow).addClass("alert-danger");
            }
        }
    });
}

// Clique no botão editar
$(document).on("click", ".btn-modal-ocorrencia", function () {
    abrirModalOcorrencia({
        ocor_id: $(this).data("id"),
        ocor_descricao: $(this).data("descricao"),
        ocor_ativo: $(this).data("ativo")
    });
});

// Ao fechar o modal
$("#modal_ocorrencia").on("hidden.bs.modal", function () {
    $("#form_ocorrencia button[type='submit']").prop("disabled", false);
});

// Abre modal
function abrirModalOcorrencia(data) {
    $("#form_ocorrencia button[type='submit']").prop("disabled", false);

    $("#modal_ocorrencia").modal({
        backdrop: "static",
        focus: true
    });

    $("#ocor_id").val(data.ocor_id || "");
    $("#ocor_descricao").val(data.ocor_descricao || "");

    const ativo = (data.ocor_ativo === 1 || data.ocor_ativo === true || data.ocor_ativo === "1");
    $("#ocor_ativo").prop("checked", ativo);
    $("#ocor_ativo_label").text(ativo ? "Ativo" : "Desativado");

    $("#modal_ocorrencia").off("shown.bs.modal").on("shown.bs.modal", function () {
        $("#form_ocorrencia button[type='submit']").prop("disabled", false);

        if ($("#ocor_id").val() === "") {
            $("#modal_ocorrencia_label").text("Ocorrências | Novo");

            $("#ocor_method").val("POST");
            $("#form_ocorrencia").attr("action", baseURL + "/ocorrencias");

            $("#div_ocor_ativo").addClass("d-none");

            $("#ocor_ativo").prop("checked", true);
            $("#ocor_ativo_label").text("Ativo");
        } else {
            $("#modal_ocorrencia_label").text("Ocorrências | Editar");

            $("#ocor_method").val("PUT");
            $("#form_ocorrencia").attr(
                "action",
                baseURL + "/ocorrencias/" + $("#ocor_id").val()
            );

            $("#div_ocor_ativo").removeClass("d-none");
        }

        $("#ocor_descricao").focus();
    });

    // Troca label ao clicar
    $("#ocor_ativo").off("change").on("change", function () {
        $("#ocor_ativo_label").text(this.checked ? "Ativo" : "Desativado");
    });
}

// Submit
function initSubmitOcorrencia() {
    $(document).off("submit", "#form_ocorrencia").on("submit", "#form_ocorrencia", function (event) {
        event.preventDefault();

        let form = $(this);
        let actionUrl = form.attr("action");

        let formData = form.serializeArray();
        const ativoMarcado = $("#ocor_ativo").is(":checked");
        formData.push({ name: "ocor_ativo", value: ativoMarcado ? 1 : 0 });

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
                $("#modal_ocorrencia").modal("hide");
                $("#dataTableOcorrencias").DataTable().ajax.reload(null, false);

                showNotification("fas fa-check-double", response.message, "success", 2000);

                form[0].reset();
                $("#ocor_id").val("");
                $("#ocor_method").val("POST");

                $("#ocor_ativo").prop("checked", true);
                $("#ocor_ativo_label").text("Ativo");
                $("#div_ocor_ativo").addClass("d-none");

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
