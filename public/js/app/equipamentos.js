// equipamentos.js

$(document).ready(function () {
    configDataTableEquipamentos();
    initSubmitEquipamento();

    $("#btnNovoEquipamento").on("click", function () {
        abrirModalEquipamento({
            equip_id: "",
            equip_descricao: "",
            equip_ativo: 1
        });
    });
});

// Configuração da tabela
function configDataTableEquipamentos() {
    const tableEl = $("#dataTableEquipamentos");
    const url = tableEl.data("url");

    tableEl.DataTable({
        ajax: {
            url: url,
            type: "GET",
            dataSrc: "data"
        },
        columns: [
            { data: "acoes" },
            { data: "equip_descricao" },
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
            if (aData.equip_ativo === 0) {
                $("td", nRow).addClass("alert-danger");
            }
        }
    });
}

// Clique no botão editar (gerado na coluna ações)
$(document).on("click", ".btn-modal-equipamento", function () {
    abrirModalEquipamento({
        equip_id: $(this).data("id"),
        equip_descricao: $(this).data("descricao"),
        equip_ativo: $(this).data("ativo")
    });
});

// Ao fechar o modal: garante botão submit habilitado
$("#modal_equipamento").on("hidden.bs.modal", function () {
    $("#form_equipamento button[type='submit']").prop("disabled", false);
});

// Abre modal e configura campos
function abrirModalEquipamento(data) {
    $("#form_equipamento button[type='submit']").prop("disabled", false);

    $("#modal_equipamento").modal({
        backdrop: "static",
        focus: true
    });

    $("#equip_id").val(data.equip_id || "");
    $("#equip_descricao").val(data.equip_descricao || "");

    const ativo = (data.equip_ativo === 1 || data.equip_ativo === true || data.equip_ativo === "1");
    $("#equip_ativo").prop("checked", ativo);
    $("#equip_ativo_label").text(ativo ? "Ativo" : "Desativado");

    $("#modal_equipamento").off("shown.bs.modal").on("shown.bs.modal", function () {
        $("#form_equipamento button[type='submit']").prop("disabled", false);

        if ($("#equip_id").val() === "") {
            $("#modal_equipamento_label").text("Equipamentos | Novo");

            $("#equip_method").val("POST");
            $("#form_equipamento").attr("action", baseURL + "/equipamentos");

            $("#div_equip_ativo").addClass("d-none");

            $("#equip_ativo").prop("checked", true);
            $("#equip_ativo_label").text("Ativo");
        } else {
            $("#modal_equipamento_label").text("Equipamentos | Editar");

            $("#equip_method").val("PUT");
            $("#form_equipamento").attr(
                "action",
                baseURL + "/equipamentos/" + $("#equip_id").val()
            );

            $("#div_equip_ativo").removeClass("d-none");
        }

        $("#equip_descricao").focus();
    });

    // Troca label ao clicar
    $("#equip_ativo").off("change").on("change", function () {
        $("#equip_ativo_label").text(this.checked ? "Ativo" : "Desativado");
    });
}

// Submit do form (store/update)
function initSubmitEquipamento() {
    $(document).off("submit", "#form_equipamento").on("submit", "#form_equipamento", function (event) {
        event.preventDefault();

        let form = $(this);
        let actionUrl = form.attr("action");

        // serialize não envia checkbox desmarcado
        let formData = form.serializeArray();
        const ativoMarcado = $("#equip_ativo").is(":checked");
        formData.push({ name: "equip_ativo", value: ativoMarcado ? 1 : 0 });

        const btnSubmit = form.find("button[type='submit']");
        btnSubmit.prop("disabled", true);

        $.ajax({
            url: actionUrl,
            type: "POST", // Laravel usa _method para PUT
            data: $.param(formData),
            dataType: "json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            },
            success: function (response) {
                $("#modal_equipamento").modal("hide");
                $("#dataTableEquipamentos").DataTable().ajax.reload(null, false);

                showNotification(
                    "fas fa-check-double",
                    response.message,
                    "success",
                    2000
                );

                form[0].reset();
                $("#equip_id").val("");
                $("#equip_method").val("POST");

                $("#equip_ativo").prop("checked", true);
                $("#equip_ativo_label").text("Ativo");
                $("#div_equip_ativo").addClass("d-none");

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