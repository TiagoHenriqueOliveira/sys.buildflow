$(document).ready(function () {
    configDataTableNaturezasAtendimentos();
    initSubmitNaturezaAtendimento();

    $("#btnNovaNaturezaAtendimento").on("click", function () {
        abrirModalNaturezaAtendimento({
            nat_aten_id: "",
            nat_aten_descricao: "",
            nat_aten_tp_atendimento_id: "",
            nat_aten_mod_relatorio_id: "",
            nat_aten_ativo: 1
        });
    });
});

function configDataTableNaturezasAtendimentos() {
    const tableEl = $("#dataTableNaturezasAtendimentos");
    const url = tableEl.data("url");

    tableEl.DataTable({
        ajax: { url: url, type: "GET", dataSrc: "data" },
        columns: [
            { data: "acoes" },
            { data: "nat_aten_descricao" },
            { data: "tp_aten_descricao" },
            { data: "mod_rel_descricao" },
            { data: "status" }
        ],
        columnDefs: [
            { width: "10%", targets: 0 },
            { width: "30%", targets: 1 },
            { width: "25%", targets: 2 },
            { width: "20%", targets: 3 },
            { width: "15%", targets: 4 }
        ],
        createdRow: function (row) {
            $("td", row).eq(0).addClass("text-center");
        },
        fnRowCallback: function (nRow, aData) {
            if (aData.nat_aten_ativo === 0) {
                $("td", nRow).addClass("alert-danger");
            }
        }
    });
}

$(document).on("click", ".btn-modal-natureza-atendimento", function () {
    abrirModalNaturezaAtendimento({
        nat_aten_id: $(this).data("id"),
        nat_aten_descricao: $(this).data("descricao"),
        nat_aten_tp_atendimento_id: $(this).data("tp-aten"),
        nat_aten_mod_relatorio_id: $(this).data("mod-rel"),
        nat_aten_ativo: $(this).data("ativo")
    });
});

$("#modal_natureza_atendimento").on("hidden.bs.modal", function () {
    $("#form_natureza_atendimento button[type='submit']").prop("disabled", false);
});

function abrirModalNaturezaAtendimento(data) {
    $("#form_natureza_atendimento button[type='submit']").prop("disabled", false);

    $("#modal_natureza_atendimento").modal({ backdrop: "static", focus: true });

    $("#nat_aten_id").val(data.nat_aten_id || "");
    $("#nat_aten_descricao").val(data.nat_aten_descricao || "");
    $("#nat_aten_tp_atendimento_id").val(data.nat_aten_tp_atendimento_id || "");
    $("#nat_aten_mod_relatorio_id").val(data.nat_aten_mod_relatorio_id || "");

    const ativo = (data.nat_aten_ativo === 1 || data.nat_aten_ativo === true || data.nat_aten_ativo === "1");
    $("#nat_aten_ativo").prop("checked", ativo);
    $("#nat_aten_ativo_label").text(ativo ? "Ativo" : "Desativado");

    $("#modal_natureza_atendimento").off("shown.bs.modal").on("shown.bs.modal", function () {
        $("#form_natureza_atendimento button[type='submit']").prop("disabled", false);

        if ($("#nat_aten_id").val() === "") {
            $("#modal_natureza_atendimento_label").text("Naturezas de Atendimento | Novo");

            $("#nat_aten_method").val("POST");
            $("#form_natureza_atendimento").attr("action", baseURL + "/naturezas-dos-atendimentos");

            $("#div_nat_aten_ativo").addClass("d-none");
            $("#nat_aten_ativo").prop("checked", true);
            $("#nat_aten_ativo_label").text("Ativo");

            $("#nat_aten_tp_atendimento_id").val("");
            $("#nat_aten_mod_relatorio_id").val("");
        } else {
            $("#modal_natureza_atendimento_label").text("Naturezas de Atendimento | Editar");

            $("#nat_aten_method").val("PUT");
            $("#form_natureza_atendimento").attr("action", baseURL + "/naturezas-dos-atendimentos/" + $("#nat_aten_id").val());

            $("#div_nat_aten_ativo").removeClass("d-none");
        }

        $("#nat_aten_descricao").focus();
    });

    $("#nat_aten_ativo").off("change").on("change", function () {
        $("#nat_aten_ativo_label").text(this.checked ? "Ativo" : "Desativado");
    });
}

// Submit permanece, apenas garantindo que os campos novos vão no serialize
function initSubmitNaturezaAtendimento() {
    $(document).off("submit", "#form_natureza_atendimento").on("submit", "#form_natureza_atendimento", function (event) {
        event.preventDefault();

        let form = $(this);
        let actionUrl = form.attr("action");
        let formData = form.serializeArray();

        formData.push({ name: "nat_aten_ativo", value: $("#nat_aten_ativo").is(":checked") ? 1 : 0 });

        const btnSubmit = form.find("button[type='submit']");
        btnSubmit.prop("disabled", true);

        $.ajax({
            url: actionUrl,
            type: "POST",
            data: $.param(formData),
            dataType: "json",
            headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
            success: function (response) {
                $("#modal_natureza_atendimento").modal("hide");
                $("#dataTableNaturezasAtendimentos").DataTable().ajax.reload(null, false);

                showNotification("fas fa-check-double", response.message, "success", 2000);

                form[0].reset();
                $("#nat_aten_id").val("");
                $("#nat_aten_method").val("POST");
                $("#nat_aten_tp_atendimento_id").val("");
                $("#nat_aten_mod_relatorio_id").val("");

                $("#nat_aten_ativo").prop("checked", true);
                $("#nat_aten_ativo_label").text("Ativo");
                $("#div_nat_aten_ativo").addClass("d-none");

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