// usuarios.js

$(document).ready(function () {
    configDataTableUsuarios();
    initSubmitUsuario();

    $("#btnNovoUsuario").on("click", function () {
        abrirModalUsuario({
            user_id: "",
            user_nome: "",
            user_email: "",
            user_nivel_acesso: "",
            user_ativo: 1
        });
    });

    // Sugerir senha (<= 10) e preencher confirmação
    $(document).on("click", "#btnSugerirSenha", function () {
        const senha = gerarSenhaProvisoria(10);
        $("#user_senha").val(senha);
        $("#user_senha_confirmation").val(senha);
        $("#user_senha").focus();
    });

    // Toggle olho (senha e confirmação)
    $(document).on("click", ".btn-toggle-password", function () {
        const target = $(this).data("target");
        togglePassword(target, this);
    });
});

// DataTable
function configDataTableUsuarios() {
    const tableEl = $("#dataTableUsuarios");
    const url = tableEl.data("url");

    tableEl.DataTable({
        ajax: { url: url, type: "GET", dataSrc: "data" },
        columns: [
            { data: "acoes" },
            { data: "user_nome" },
            { data: "user_email" },
            { data: "nivel" },
            { data: "status" }
        ],
        columnDefs: [
            { width: "10%", targets: 0 },
            { width: "30%", targets: 1 },
            { width: "30%", targets: 2 },
            { width: "15%", targets: 3 },
            { width: "15%", targets: 4 }
        ],
        createdRow: function (row) {
            $("td", row).eq(0).addClass("text-center");
        },
        fnRowCallback: function (nRow, aData) {
            if (aData.user_ativo === 0) {
                $("td", nRow).addClass("alert-danger");
            }
        }
    });
}

// Editar
$(document).on("click", ".btn-modal-usuario", function () {
    abrirModalUsuario({
        user_id: $(this).data("id"),
        user_nome: $(this).data("nome"),
        user_email: $(this).data("email"),
        user_nivel_acesso: $(this).data("nivel"),
        user_ativo: $(this).data("ativo")
    });
});

// Modal hidden
$("#modal_usuario").on("hidden.bs.modal", function () {
    $("#form_usuario button[type='submit']").prop("disabled", false);

    // volta para oculto + ícone olho
    resetPasswordField("#user_senha");
    resetPasswordField("#user_senha_confirmation");
});

// Abre modal
function abrirModalUsuario(data) {
    $("#form_usuario button[type='submit']").prop("disabled", false);

    $("#modal_usuario").modal({ backdrop: "static", focus: true });

    $("#user_id").val(data.user_id || "");
    $("#user_nome").val(data.user_nome || "");
    $("#user_email").val(data.user_email || "");

    // radio nível
    $("input[name='user_nivel_acesso']").prop("checked", false);
    if (data.user_nivel_acesso === 0 || data.user_nivel_acesso === "0") {
        $("#nivel_admin").prop("checked", true);
    } else if (data.user_nivel_acesso === 1 || data.user_nivel_acesso === "1") {
        $("#nivel_tecnico").prop("checked", true);
    }

    // ativo
    const ativo = (data.user_ativo === 1 || data.user_ativo === true || data.user_ativo === "1");
    $("#user_ativo").prop("checked", ativo);
    $("#user_ativo_label").text(ativo ? "Ativo" : "Desativado");

    $("#modal_usuario").off("shown.bs.modal").on("shown.bs.modal", function () {
        $("#form_usuario button[type='submit']").prop("disabled", false);

        const isCreate = ($("#user_id").val() === "");

        // limpa campos de senha sempre
        $("#user_senha").val("");
        $("#user_senha_confirmation").val("");

        if (isCreate) {
            $("#modal_usuario_label").text("Usuários | Novo");
            $("#user_method").val("POST");
            $("#form_usuario").attr("action", baseURL + "/usuarios");

            $("#senha_help").show();
            $("#div_user_ativo").addClass("d-none");
        } else {
            $("#modal_usuario_label").text("Usuários | Editar");
            $("#user_method").val("PUT");
            $("#form_usuario").attr("action", baseURL + "/usuarios/" + $("#user_id").val());

            $("#senha_help").show();
            $("#div_user_ativo").removeClass("d-none");
        }

        $("#user_nome").focus();
    });

    // label ativo
    $("#user_ativo").off("change").on("change", function () {
        $("#user_ativo_label").text(this.checked ? "Ativo" : "Desativado");
    });
}

// Submit
function initSubmitUsuario() {
    $(document).off("submit", "#form_usuario").on("submit", "#form_usuario", function (event) {
        event.preventDefault();

        let form = $(this);
        let actionUrl = form.attr("action");

        let formData = form.serializeArray();

        // garante envio do ativo
        const ativoMarcado = $("#user_ativo").is(":checked");
        formData.push({ name: "user_ativo", value: ativoMarcado ? 1 : 0 });

        const btnSubmit = form.find("button[type='submit']");
        btnSubmit.prop("disabled", true);

        $.ajax({
            url: actionUrl,
            type: "POST",
            data: $.param(formData),
            dataType: "json",
            headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
            success: function (response) {
                $("#modal_usuario").modal("hide");
                $("#dataTableUsuarios").DataTable().ajax.reload(null, false);

                showNotification("fas fa-check-double", response.message, "success", 2000);

                form[0].reset();
                $("#user_id").val("");
                $("#user_method").val("POST");
                $("#div_user_ativo").addClass("d-none");

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

// Toggle password visibility + ícone
function togglePassword(inputSelector, btn) {
    const input = document.querySelector(inputSelector);
    if (!input) return;

    const icon = btn.querySelector("i");

    if (input.type === "password") {
        input.type = "text";
        if (icon) {
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        }
    } else {
        input.type = "password";
        if (icon) {
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
}

function resetPasswordField(inputSelector) {
    const input = document.querySelector(inputSelector);
    if (!input) return;

    input.type = "password";

    const btn = document.querySelector(`.btn-toggle-password[data-target="${inputSelector}"]`);
    if (btn) {
        const icon = btn.querySelector("i");
        if (icon) {
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
}

// Gerador seguro (<= 10 chars)
function gerarSenhaProvisoria(length) {
    const chars = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@#$%";
    length = Math.min(parseInt(length || 10, 10), 10);

    if (window.crypto && window.crypto.getRandomValues) {
        const array = new Uint32Array(length);
        window.crypto.getRandomValues(array);
        return Array.from(array, x => chars[x % chars.length]).join("");
    }

    let senha = "";
    for (let i = 0; i < length; i++) {
        senha += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return senha;
}