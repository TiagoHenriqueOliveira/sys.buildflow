// mao.de.obra.js

$(document).ready(function () {
    configDataTableMaoDeObra();
    initSubmitMaoDeObra();

    $("#btnNovaMaoDeObra").on("click", function () {
        abrirModalMaoDeObra({
            ocup_id: "",
            ocup_tp_ocupacao_id: "",
            ocup_descricao: "",
            ocup_ativo: 1
        });
    });
});

// Configuração da tabela
function configDataTableMaoDeObra() {
    const tableEl = $("#dataTableMaoDeObra");
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
            { data: "ocup_descricao" },
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
            if (aData.ocup_ativo === 0) {
                $("td", nRow).addClass("alert-danger");
            }
        }
    });
}

// Clique no botão editar
$(document).on("click", ".btn-modal-mao-de-obra", function () {
    abrirModalMaoDeObra({
        ocup_id: $(this).data("id"),
        ocup_tp_ocupacao_id: $(this).data("tp-id"),
        ocup_descricao: $(this).data("descricao"),
        ocup_ativo: $(this).data("ativo")
    });
});

// Ao fechar modal
$("#modal_mao_de_obra").on("hidden.bs.modal", function () {
    $("#form_mao_de_obra button[type='submit']").prop("disabled", false);
});

// Abre modal e configura campos
function abrirModalMaoDeObra(data) {
    $("#form_mao_de_obra button[type='submit']").prop("disabled", false);

    $("#modal_mao_de_obra").modal({
        backdrop: "static",
        focus: true
    });

    $("#ocup_id").val(data.ocup_id || "");
    $("#ocup_descricao").val(data.ocup_descricao || "");
    $("#ocup_tp_ocupacao_id").val(data.ocup_tp_ocupacao_id || "");

    const ativo = (data.ocup_ativo === 1 || data.ocup_ativo === true || data.ocup_ativo === "1");
    $("#ocup_ativo").prop("checked", ativo);
    $("#ocup_ativo_label").text(ativo ? "Ativo" : "Desativado");

    $("#modal_mao_de_obra").off("shown.bs.modal").on("shown.bs.modal", function () {
        $("#form_mao_de_obra button[type='submit']").prop("disabled", false);

        if ($("#ocup_id").val() === "") {
            $("#modal_mao_de_obra_label").text("Mão de Obra | Novo");

            $("#ocup_method").val("POST");
            $("#form_mao_de_obra").attr("action", baseURL + "/mao-de-obra");

            $("#div_ocup_ativo").addClass("d-none");

            $("#ocup_ativo").prop("checked", true);
            $("#ocup_ativo_label").text("Ativo");
        } else {
            $("#modal_mao_de_obra_label").text("Mão de Obra | Editar");

            $("#ocup_method").val("PUT");
            $("#form_mao_de_obra").attr(
                "action",
                baseURL + "/mao-de-obra/" + $("#ocup_id").val()
            );

            $("#div_ocup_ativo").removeClass("d-none");
        }

        $("#ocup_tp_ocupacao_id").focus();
    });

    // Troca label ao clicar
    $("#ocup_ativo").off("change").on("change", function () {
        $("#ocup_ativo_label").text(this.checked ? "Ativo" : "Desativado");
    });
}

// Submit do form
function initSubmitMaoDeObra() {
    $(document).off("submit", "#form_mao_de_obra").on("submit", "#form_mao_de_obra", function (event) {
        event.preventDefault();

        let form = $(this);
        let actionUrl = form.attr("action");

        let formData = form.serializeArray();
        const ativoMarcado = $("#ocup_ativo").is(":checked");
        formData.push({ name: "ocup_ativo", value: ativoMarcado ? 1 : 0 });

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
                $("#modal_mao_de_obra").modal("hide");
                $("#dataTableMaoDeObra").DataTable().ajax.reload(null, false);

                showNotification("fas fa-check-double", response.message, "success", 2000);

                form[0].reset();
                $("#ocup_id").val("");
                $("#ocup_method").val("POST");
                $("#ocup_tp_ocupacao_id").val("");

                $("#ocup_ativo").prop("checked", true);
                $("#ocup_ativo_label").text("Ativo");
                $("#div_ocup_ativo").addClass("d-none");

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