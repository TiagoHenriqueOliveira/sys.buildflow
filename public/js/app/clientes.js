// clientes.js (padrão equipamentos + render masks + format no editar)

$(document).ready(function () {
    configDataTableClientes();
    initSubmitCliente();

    $("#btnNovoCliente").on("click", function () {
        abrirModalCliente({
            cli_id: "",
            cli_nome: "",
            cli_cnpj: "",
            cli_cidade: "",
            cli_uf: "",
            cli_telefone: "",
            cli_email: "",
            cli_ativo: 1
        });
    });
});

// helpers de formatação (para exibição no DataTable e no modal)
function onlyDigits(value) {
    return (value || "").toString().replace(/\D+/g, "");
}

function formatCnpj(value) {
    const v = onlyDigits(value);
    if (v.length !== 14) return value || "";
    return v.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, "$1.$2.$3/$4-$5");
}

function formatTelefone(value) {
    const v = onlyDigits(value);
    if (v.length === 11) {
        return v.replace(/^(\d{2})(\d{5})(\d{4})$/, "($1) $2-$3");
    }
    if (v.length === 10) {
        return v.replace(/^(\d{2})(\d{4})(\d{4})$/, "($1) $2-$3");
    }
    return value || "";
}

// Configuração da tabela
function configDataTableClientes() {
    const tableEl = $("#dataTableClientes");
    const url = tableEl.data("url");

    tableEl.DataTable({
        ajax: {
            url: url,
            type: "GET",
            dataSrc: "data"
        },
        serverSide: true,
        processing: true,
        stateSave: true,
        columns: [
            { data: "acoes" },
            { data: "cli_nome" },
            {
                data: "cli_cnpj",
                render: function (data) { return formatCnpj(data); }
            },
            { data: "cli_cidade" },
            { data: "cli_uf" },
            {
                data: "cli_telefone",
                render: function (data) { return formatTelefone(data); }
            },
            { data: "cli_email" },
            { data: "status" }
        ],
        columnDefs: [
            { width: "4%", targets: 0 },
            { width: "30%", targets: 1 },
            { width: "12%", targets: 2 },
            { width: "15%", targets: 3 },
            { width: "5%", targets: 4 },
            { width: "10%", targets: 5 },
            { width: "18%", targets: 6 },
            { width: "6%", targets: 7 }
        ],
        createdRow: function (row) {
            $("td", row).eq(0).addClass("text-center");
        },
        fnRowCallback: function (nRow, aData) {
            if (aData.cli_ativo === 0) {
                $("td", nRow).addClass("alert-danger");
            }
        }
    });
}

// Clique no botão editar
$(document).on("click", ".btn-modal-cliente", function () {
    abrirModalCliente({
        cli_id: $(this).data("id"),
        cli_nome: $(this).data("nome"),
        cli_cnpj: $(this).data("cnpj"),
        cli_cidade: $(this).data("cidade"),
        cli_uf: $(this).data("uf"),
        cli_telefone: $(this).data("telefone"),
        cli_email: $(this).data("email"),
        cli_ativo: $(this).data("ativo")
    });
});

// Ao fechar modal: garante submit habilitado
$("#modal_cliente").on("hidden.bs.modal", function () {
    $("#form_cliente button[type='submit']").prop("disabled", false);
});

// Abre modal e configura campos
function abrirModalCliente(data) {
    $("#form_cliente button[type='submit']").prop("disabled", false);

    $("#modal_cliente").modal({
        backdrop: "static",
        focus: true
    });

    $("#cli_id").val(data.cli_id || "");
    $("#cli_nome").val(data.cli_nome || "");

    $("#cli_cnpj").val(formatCnpj(data.cli_cnpj || ""));
    $("#cli_cidade").val(data.cli_cidade || "");
    $("#cli_uf").val((data.cli_uf || "").toString().toUpperCase());
    $("#cli_telefone").val(formatTelefone(data.cli_telefone || ""));
    $("#cli_email").val(data.cli_email || "");

    const ativo = (data.cli_ativo === 1 || data.cli_ativo === true || data.cli_ativo === "1");
    $("#cli_ativo").prop("checked", ativo);
    $("#cli_ativo_label").text(ativo ? "Ativo" : "Desativado");

    $("#modal_cliente").off("shown.bs.modal").on("shown.bs.modal", function () {
        $("#form_cliente button[type='submit']").prop("disabled", false);

        if ($("#cli_id").val() === "") {
            $("#modal_cliente_label").text("Clientes | Novo");

            $("#cli_method").val("POST");
            $("#form_cliente").attr("action", baseURL + "/clientes");

            $("#div_cli_ativo").addClass("d-none");

            $("#cli_ativo").prop("checked", true);
            $("#cli_ativo_label").text("Ativo");
        } else {
            $("#modal_cliente_label").text("Clientes | Editar");

            $("#cli_method").val("PUT");
            $("#form_cliente").attr(
                "action",
                baseURL + "/clientes/" + $("#cli_id").val()
            );

            $("#div_cli_ativo").removeClass("d-none");
        }

        $("#cli_nome").focus();
    });

    // Troca label ao clicar
    $("#cli_ativo").off("change").on("change", function () {
        $("#cli_ativo_label").text(this.checked ? "Ativo" : "Desativado");
    });
}

// helper: substitui campo no serializeArray
function upsertField(formData, name, value) {
    for (let i = formData.length - 1; i >= 0; i--) {
        if (formData[i].name === name) {
            formData.splice(i, 1);
        }
    }
    formData.push({ name: name, value: value });
}

// Submit do form (store/update)
function initSubmitCliente() {
    $(document).off("submit", "#form_cliente").on("submit", "#form_cliente", function (event) {
        event.preventDefault();

        let form = $(this);
        let actionUrl = form.attr("action");

        // sanitiza antes de enviar (remove máscara)
        const cnpjDigits = onlyDigits($("#cli_cnpj").val());
        const telDigits = onlyDigits($("#cli_telefone").val());
        const ufUpper = ($("#cli_uf").val() || "").toString().trim().toUpperCase();

        let formData = form.serializeArray();

        upsertField(formData, "cli_cnpj", cnpjDigits);
        upsertField(formData, "cli_telefone", telDigits);
        upsertField(formData, "cli_uf", ufUpper);

        // checkbox ativo
        const ativoMarcado = $("#cli_ativo").is(":checked");
        formData.push({ name: "cli_ativo", value: ativoMarcado ? 1 : 0 });

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
                $("#modal_cliente").modal("hide");
                $("#dataTableClientes").DataTable().ajax.reload(null, false);

                showNotification("fas fa-check-double", response.message, "success", 2000);

                form[0].reset();
                $("#cli_id").val("");
                $("#cli_method").val("POST");

                $("#cli_ativo").prop("checked", true);
                $("#cli_ativo_label").text("Ativo");
                $("#div_cli_ativo").addClass("d-none");

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