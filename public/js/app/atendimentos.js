// atendimentos.js (padrão Equipamentos)

$(document).ready(function () {
    configDataTableAtendimentos();
    initSubmitAtendimento();

    $("#btnNovoAtendimento").on("click", function () {
        abrirModalAtendimento({
            aten_id: "",
            aten_tp_atendimento_id: "",
            aten_cliente_id: "",
            aten_cliente_nome: "",
            aten_usuario_id: "",
            aten_status: 0,
            aten_dt_inicio: "",
            aten_dt_fim: ""
        });
    });

    setupAutocomplete(
        "#aten_cliente_nome",
        "#aten_cliente_id",
        baseURL + "/atendimento/autocomplete"
    );
});

// DataTable
function configDataTableAtendimentos() {
    const tableEl = $("#dataTableAtendimentos");
    const url = tableEl.data("url");

    tableEl.DataTable({
        ajax: {
            url: url,
            type: "GET",
            dataSrc: "data"
        },
        columns: [
            { data: "acoes" },
            { data: "tipo" },
            { data: "cliente" },
            { data: "usuario" },
            { data: "periodo" },
            { data: "status" }
        ],
        columnDefs: [
            { width: "10%", targets: 0 }
        ],
        createdRow: function (row) {
            $("td", row).eq(0).addClass("text-center");
        }
    });
}

// Editar
$(document).on("click", ".btn-modal-atendimento", function () {
    abrirModalAtendimento({
        aten_id: $(this).data("id"),
        aten_tp_atendimento_id: $(this).data("tp"),
        aten_cliente_id: $(this).data("cliente-id"),
        aten_cliente_nome: $(this).data("cliente"),
        aten_usuario_id: $(this).data("usuario-id"),
        aten_status: $(this).data("status"),
        aten_dt_inicio: $(this).data("dt-inicio"),
        aten_dt_fim: $(this).data("dt-fim")
    });
});

// Ao fechar modal
$("#modal_atendimento").on("hidden.bs.modal", function () {
    $("#form_atendimento button[type='submit']").prop("disabled", false);
});

// Modal
function abrirModalAtendimento(data) {
    $("#form_atendimento button[type='submit']").prop("disabled", false);

    $("#modal_atendimento").modal({
        backdrop: "static",
        focus: true
    });

    $("#aten_id").val(data.aten_id || "");
    $("#aten_tp_atendimento_id").val(data.aten_tp_atendimento_id || "");
    $("#aten_cliente_id").val(data.aten_cliente_id || "");
    $("#aten_cliente_nome").val(data.aten_cliente_nome || "");
    $("#aten_usuario_id").val(data.aten_usuario_id || "");

    // Status (radio inline)
    $("input[name='aten_status']").prop("checked", false);
    $(`#aten_status_${data.aten_status ?? 0}`).prop("checked", true);

    $("#aten_dt_inicio").val(data.aten_dt_inicio || "");
    $("#aten_dt_fim").val(data.aten_dt_fim || "");

    $("#modal_atendimento").off("shown.bs.modal").on("shown.bs.modal", function () {
        if ($("#aten_id").val() === "") {
            $("#modal_atendimento_label").text("Atendimentos | Novo");

            $("#aten_method").val("POST");
            $("#form_atendimento").attr("action", baseURL + "/atendimentos");

            $("#aten_status_0").prop("checked", true);
        } else {
            $("#modal_atendimento_label").text("Atendimentos | Editar");

            $("#aten_method").val("PUT");
            $("#form_atendimento").attr(
                "action",
                baseURL + "/atendimentos/" + $("#aten_id").val()
            );
        }
    });
}

// Submit
function initSubmitAtendimento() {
    $(document).off("submit", "#form_atendimento").on("submit", "#form_atendimento", function (event) {
        event.preventDefault();

        let form = $(this);
        let actionUrl = form.attr("action");
        let formData = form.serializeArray();

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
                $("#modal_atendimento").modal("hide");
                $("#dataTableAtendimentos").DataTable().ajax.reload(null, false);

                showNotification("fas fa-check-double", response.message, "success", 2000);

                form[0].reset();
                $("#aten_status_0").prop("checked", true);
                btnSubmit.prop("disabled", false);
            },
            error: function (xhr) {
                btnSubmit.prop("disabled", false);

                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessages = "<ul>";

                    $.each(errors, function (k, v) {
                        v.forEach(msg => errorMessages += `<li>${msg}</li>`);
                    });

                    errorMessages += "</ul>";

                    showNotification("fas fa-bug", errorMessages, "danger", 5000);
                } else {
                    showNotification("fas fa-bug", "Erro inesperado.", "danger", 5000);
                }
            }
        });
    });
}