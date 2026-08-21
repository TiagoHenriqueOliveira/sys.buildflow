// atendimentos.js

$(document).ready(function () {
    try {
        configDataTableAtendimentos();
    } catch (e) {
        console.error("Erro ao inicializar DataTable:", e);
    }

    initSubmitAtendimento();

    $("#btnNovoAtendimento").on("click", function () {
        abrirModalAtendimento({
            aten_id: "",
            aten_tp_atendimento_id: "",
            aten_natureza_id: "",
            aten_cliente_id: "",
            aten_cliente_nome: "",
            aten_usuario_id: "",
            aten_status: 0,
            aten_nr_proposta: "",
            aten_contato: "",
            aten_responsavel: "",
            aten_endereco: "",
            aten_dt_inicio: "",
            aten_dt_fim: ""
        });
    });

    setupAutocomplete("#aten_cliente_nome", "#aten_cliente_id", baseURL + "/clientes/autocomplete");

    // Máscara de telefone
    $('#aten_telefone').mask('(00) 00000-0000');

    // Switch entrega técnica
    $(document).on('change', '#aten_entrega_tecnica', function () {
        $('#aten_entrega_tecnica_badge')
            .text(this.checked ? 'Sim' : 'Não')
            .toggleClass('badge-success', this.checked)
            .toggleClass('badge-secondary', !this.checked);
    });

    // WhatsApp
    $(document).on('input', '#aten_telefone', function () {
        atualizarLinkWhatsapp();
    });

    $(document).on("change", "#aten_nat_atendimento_id, #aten_natureza_id, #aten_natureza_select", function () {
        syncHiddenFromSelects();
    });

    $("#modal_atendimento").on("hidden.bs.modal", function () {
        $("#form_atendimento button[type='submit']").prop("disabled", false);
        $("#aten_equip_descricao").val("");
        $("#table_equipamentos tbody").empty();
        $("#aten_obs_tecnica, #aten_obs_cliente, #aten_obs_manutencao").val("");
        $("#aten_anexos_lista").empty();
        $("#aten_usuario_id").val("");
        $("#aten_telefone").val("");
        $("#aten_entrega_tecnica").prop("checked", false);
        $("#aten_entrega_tecnica_badge").text("Não").removeClass("badge-success").addClass("badge-secondary");
        $("#tab-equipamentos-tab, #tab-observacoes-tab, #tab-anexos-aten-tab").addClass("disabled").prop("disabled", true);
        atualizarIconeAba("tab-equipamentos-tab", false);
        atualizarIconeAba("tab-observacoes-tab", false);
        atualizarIconeAba("tab-anexos-aten-tab", false);
        $("#tab-dados-tab").tab("show");
    });
});

function configDataTableAtendimentos() {
    const tableEl = $("#dataTableAtendimentos");
    if (!tableEl.length) return;

    if ($.fn.DataTable.isDataTable(tableEl)) {
        return tableEl.DataTable();
    }

    const url     = tableEl.data("url");
    const isAdmin = tableEl.data("admin") === 1 || tableEl.data("admin") === "1";

    return tableEl.DataTable({
        ajax: {
            url: url,
            type: "GET",
            dataSrc: "data",
            error: function (xhr) {
                console.error("DataTables AJAX error:", xhr.status, xhr.responseText);
                showNotification("fas fa-bug", "Erro ao carregar dados. Código: " + xhr.status, "danger", 5000);
            }
        },
        serverSide: true,
        processing: true,
        stateSave: true,
        columns: [
            { data: "acoes" },
            { data: "natureza" },
            { data: "usuario" },
            { data: "cliente" },
            { data: "nr_proposta" },
            { data: "periodo" },
            { data: "status" }
        ],
        columnDefs: [
            { width: "5%", targets: 0, className: "text-center" },
            { visible: isAdmin, targets: 0 },
        ],
        createdRow: function (row) {
        },
        fnRowCallback: function (nRow, aData) {
            const concluida = 3;
            const hoje = new Date().toISOString().slice(0, 10);
            if (aData.aten_status !== concluida && aData.aten_dt_fim < hoje) {
                $(nRow).addClass("row-atrasado");
            }
        }
    });
}

$(document).on("click", ".btn-modal-atendimento", function () {
    abrirModalAtendimento({
        aten_id: $(this).data("id"),
        aten_tp_atendimento_id: $(this).data("tp"),
        aten_natureza_id: $(this).data("natureza-id"),
        aten_cliente_id: $(this).data("cliente-id"),
        aten_cliente_nome: $(this).data("cliente"),
        aten_usuario_id: $(this).data("usuario-id"),
        aten_status: $(this).data("status"),
        aten_nr_proposta:     $(this).data("nr-proposta"),
        aten_contato:         $(this).data("contato"),
        aten_responsavel:     $(this).data("responsavel"),
        aten_telefone:        $(this).data("telefone"),
        aten_entrega_tecnica: $(this).data("entrega-tecnica"),
        aten_endereco:        $(this).data("endereco"),
        aten_dt_inicio: $(this).data("dt-inicio"),
        aten_dt_fim: $(this).data("dt-fim")
    });
});

function getNaturezaSelect() {
    let s = $("#aten_nat_atendimento_id");
    if (s.length) return s;

    s = $("#aten_natureza_id");
    if (s.length) return s;

    s = $("#aten_natureza_select");
    if (s.length) return s;

    return $();
}

function ensureHiddenInputs() {
    const form = $("#form_atendimento");
    if (!form.length) return;

    if (!$("#aten_natureza_id_hidden").length) {
        form.append('<input type="hidden" name="aten_natureza_id" id="aten_natureza_id_hidden">');
    }
}

function syncHiddenFromSelects() {
    ensureHiddenInputs();

    const naturezaSelect = getNaturezaSelect();
    const naturezaVal = naturezaSelect.length ? (naturezaSelect.val() || "") : "";

    $("#aten_natureza_id_hidden").val(naturezaVal);
}

function abrirModalAtendimento(data) {
    $("#form_atendimento button[type='submit']").prop("disabled", false);
    $("#modal_atendimento").modal({ backdrop: "static", focus: true });

    const naturezaSelect = getNaturezaSelect();

    $("#aten_id").val(data.aten_id || "");

    $("#aten_cliente_id").val(data.aten_cliente_id || "");
    $("#aten_cliente_nome").val(data.aten_cliente_nome || "");
    $("#aten_nr_proposta").val(data.aten_nr_proposta || "");
    $("#aten_contato").val(data.aten_contato || "");
    $("#aten_responsavel").val(data.aten_responsavel || "");
    const tel = data.aten_telefone || "";
    $("#aten_telefone").val(tel);
    atualizarLinkWhatsapp(tel);
    const entrega = !!data.aten_entrega_tecnica;
    $("#aten_entrega_tecnica").prop("checked", entrega);
    $("#aten_entrega_tecnica_badge")
        .text(entrega ? "Sim" : "Não")
        .toggleClass("badge-success", entrega)
        .toggleClass("badge-secondary", !entrega);
    $("#aten_endereco").val(data.aten_endereco || "");
    $("#aten_dt_inicio").val(data.aten_dt_inicio || "");
    $("#aten_dt_fim").val(data.aten_dt_fim || "");

    $("input[name='aten_status']").prop("checked", false);
    $(`#aten_status_${data.aten_status ?? 0}`).prop("checked", true);

    function setupModal() {
        const isEdit = !!data.aten_id;

        if (!isEdit) {
            $("#modal_atendimento_label").text("Atendimentos | Novo");
            $("#aten_method").val("POST");
            $("#form_atendimento").attr("action", baseURL + "/atendimentos");

            if (naturezaSelect.length) {
                naturezaSelect.prop("disabled", false).val("");
            }

            syncHiddenFromSelects();

        } else {
            $("#modal_atendimento_label").text("Atendimentos | Editar");
            $("#aten_method").val("PUT");
            $("#form_atendimento").attr("action", baseURL + "/atendimentos/" + data.aten_id);

            if (naturezaSelect.length) {
                naturezaSelect.val(String(data.aten_natureza_id)).prop("disabled", false);
            }

            $("#aten_usuario_id").val(String(data.aten_usuario_id || ""));

            syncHiddenFromSelects();

            // Habilitar aba de equipamentos
            $("#tab-equipamentos-tab").removeClass("disabled").prop("disabled", false);
        }

        if (isEdit) {
            // Habilitar abas de edição
            $("#tab-observacoes-tab, #tab-anexos-aten-tab").removeClass("disabled").prop("disabled", false);

            // Carregar observações, equipamentos e anexos ao abrir o modal (para exibir check icons)
            carregarObservacoes(data.aten_id);
            carregarEquipamentos(data.aten_id);
            carregarAnexosAtendimento(data.aten_id);

            // Recarregar ao clicar nas abas
            $("#tab-anexos-aten-tab").off("click.anx").on("click.anx", function () {
                carregarAnexosAtendimento(data.aten_id);
            });
        }

        // Carregar equipamentos quando a aba é selecionada
        $("#tab-equipamentos-tab").off("click").on("click", function () {
            carregarEquipamentos(data.aten_id);
        });

        // Inicializar o submit do formulário de equipamentos
        initSubmitEquipamento();
    }

    const modalEl = $("#modal_atendimento");
    modalEl.off("shown.bs.modal").on("shown.bs.modal", setupModal);

    // Se o modal já está visível (transição de novo → edição), executa setup direto
    if (modalEl.hasClass("show")) {
        setupModal();
    }
}

function atualizarLinkWhatsapp(tel) {
    const numero = (tel !== undefined ? tel : $("#aten_telefone").val()).replace(/\D/g, "");
    const link = numero.length >= 10 ? "https://wa.me/55" + numero : "#";
    $("#btnWhatsapp").attr("href", link);
}

function atualizarIconeAba(tabId, temDados) {
    const tab = $("#" + tabId);
    const texto = tab.text().replace(/\s*✔.*$/, "").trim();
    tab.text(temDados ? texto + " ✔" : texto);
}

function carregarObservacoes(atenId) {
    $.ajax({
        url: baseURL + "/atendimentos/" + atenId + "/observacoes",
        type: "GET",
        dataType: "json",
        success: function (data) {
            $("#aten_obs_tecnica").val(data.aten_obs_tecnica || "");
            $("#aten_obs_cliente").val(data.aten_obs_cliente || "");
            $("#aten_obs_manutencao").val(data.aten_obs_manutencao || "");
            const temObs = !!(data.aten_obs_tecnica || data.aten_obs_cliente || data.aten_obs_manutencao);
            atualizarIconeAba("tab-observacoes-tab", temObs);
        }
    });
}

function carregarAnexosAtendimento(atenId) {
    $.ajax({
        url: baseURL + "/atendimentos/" + atenId + "/anexos",
        type: "GET",
        dataType: "json",
        success: function (r) {
            renderizarAnexosAtendimento(r.anexos, atenId);
        }
    });
}

function renderizarAnexosAtendimento(anexos, atenId) {
    atualizarIconeAba("tab-anexos-aten-tab", anexos && anexos.length > 0);
    const container = $("#aten_anexos_lista");
    container.empty();

    if (!anexos || !anexos.length) {
        container.html('<p class="text-muted text-center">Nenhum anexo cadastrado.</p>');
        return;
    }

    let html = '<ul class="list-group">';
    anexos.forEach(function (a) {
        html += `<li class="list-group-item d-flex justify-content-between align-items-center">
            <a href="/midia/${e(a.aten_anexo_path)}" target="_blank">${e(a.aten_anexo_nome_original)}</a>
            <button type="button" class="btn btn-danger btn-sm btn-delete-anexo-aten"
                data-id="${a.aten_anexo_id}" data-aten-id="${atenId}">
                <i class="fas fa-trash"></i>
            </button>
        </li>`;
    });
    html += '</ul>';
    container.html(html);
}

$("#aten_anexo_file").on("change", function () {
    const names = Array.from(this.files).map(f => f.name).join(", ");
    $("#aten_anexo_nome").text(names || "Selecione arquivos para upload");
});

$("#btnUploadAnexoAten").on("click", function () {
    const atenId = $("#aten_id").val();
    const files = $("#aten_anexo_file")[0].files;
    if (!atenId || !files.length) return;

    const formData = new FormData();
    Array.from(files).forEach(f => formData.append("arquivos[]", f));

    $.ajax({
        url: baseURL + "/atendimentos/" + atenId + "/upload-anexos",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
        success: function (r) {
            showNotification("fas fa-check-double", r.message, "success", 2000);
            $("#aten_anexo_file").val("");
            $("#aten_anexo_nome").text("Selecione arquivos para upload");
            carregarAnexosAtendimento(atenId);
        },
        error: function (xhr) {
            showNotification("fas fa-bug", "Erro ao enviar arquivo(s). Código: " + xhr.status, "danger", 4000);
        }
    });
});

$(document).on("click", ".btn-delete-anexo-aten", function () {
    const id = $(this).data("id");
    const atenId = $(this).data("aten-id");

    $.ajax({
        url: baseURL + "/atendimentos/" + atenId + "/anexos/" + id,
        type: "DELETE",
        dataType: "json",
        headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
        success: function (r) {
            showNotification("fas fa-check-double", r.message, "success", 2000);
            carregarAnexosAtendimento(atenId);
        },
        error: function (xhr) {
            showNotification("fas fa-bug", "Erro ao remover anexo. Código: " + xhr.status, "danger", 4000);
        }
    });
});

function initSubmitAtendimento() {
    $(document).off("submit", "#form_atendimento").on("submit", "#form_atendimento", function (event) {
        event.preventDefault();

        syncHiddenFromSelects();

        const form = $(this);
        const actionUrl = form.attr("action");
        const formData = form.serializeArray();

        const btnSubmit = form.find("button[type='submit']");
        btnSubmit.prop("disabled", true);

        $.ajax({
            url: actionUrl,
            type: "POST",
            data: $.param(formData),
            dataType: "json",
            headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
            success: function (response) {
                showNotification("fas fa-check-double", response.message, "success", 2000);
                btnSubmit.prop("disabled", false);
                $("#dataTableAtendimentos").DataTable().ajax.reload(null, false);

                if (response.aten_id && response.atendimento) {
                    // Novo atendimento: reabre em modo edição sem fechar o modal
                    abrirModalAtendimento(response.atendimento);
                } else {
                    // Edição: atualiza ícone da aba observações imediatamente
                    const atenId = $("#aten_id").val();
                    if (atenId) carregarObservacoes(atenId);
                }
            },
            error: function (xhr) {
                btnSubmit.prop("disabled", false);

                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};
                    let msg = "<ul>";

                    $.each(errors, function (k, v) {
                        (v || []).forEach(m => msg += `<li>${m}</li>`);
                    });

                    msg += "</ul>";

                    showNotification(
                        "fas fa-bug",
                        "Ocorreram erros ao salvar:<br>" + msg,
                        "danger",
                        5000
                    );
                } else {
                    console.error("Erro submit:", xhr.status, xhr.responseText);
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

function initSubmitEquipamento() {
    $(document).off("click", "#btnAdicionarEquipamento").on("click", "#btnAdicionarEquipamento", function () {
        const atenId = $("#aten_id").val();
        const actionUrl = baseURL + "/atendimentos/" + atenId + "/equipamentos";

        const fd = new FormData();
        fd.append('aten_equip_descricao', $('#aten_equip_descricao').val() || '');

        const btnSubmit = $(this);
        btnSubmit.prop("disabled", true);

        $.ajax({
            url: actionUrl,
            type: "POST",
            data: fd,
            processData: false,
            contentType: false,
            dataType: "json",
            headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
            success: function (response) {
                $('#aten_equip_descricao').val('');
                carregarEquipamentos(atenId);
                showNotification("fas fa-check-double", response.message, "success", 2000);
                btnSubmit.prop("disabled", false);
            },
            error: function (xhr) {
                btnSubmit.prop("disabled", false);

                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};
                    let msg = "<ul>";

                    $.each(errors, function (k, v) {
                        (v || []).forEach(m => msg += `<li>${m}</li>`);
                    });

                    msg += "</ul>";

                    showNotification(
                        "fas fa-bug",
                        "Ocorreram erros ao salvar:<br>" + msg,
                        "danger",
                        5000
                    );
                } else {
                    console.error("Erro ao adicionar equipamento:", xhr.status, xhr.responseText);
                    showNotification(
                        "fas fa-bug",
                        "Erro ao adicionar equipamento. Código: " + xhr.status,
                        "danger",
                        5000
                    );
                }
            }
        });
    });
}

function carregarEquipamentos(atenId) {
    if (!atenId) return;

    $.ajax({
        url: baseURL + "/atendimentos/" + atenId + "/equipamentos",
        type: "GET",
        dataType: "json",
        success: function (response) {
            renderizarEquipamentos(response.equipamentos, atenId);
        },
        error: function (xhr) {
            console.error("Erro ao carregar equipamentos:", xhr.status, xhr.responseText);
            showNotification(
                "fas fa-bug",
                "Erro ao carregar equipamentos. Código: " + xhr.status,
                "danger",
                5000
            );
        }
    });
}

function renderizarEquipamentos(equipamentos, atenId) {
    atualizarIconeAba("tab-equipamentos-tab", equipamentos && equipamentos.length > 0);
    const tbody = $("#table_equipamentos tbody");
    tbody.empty();

    if (!equipamentos || equipamentos.length === 0) {
        tbody.append("<tr><td colspan='2' class='text-center text-muted'>Nenhum equipamento cadastrado</td></tr>");
        return;
    }

    equipamentos.forEach(equip => {
        const row = `
            <tr>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm btn-delete-equip" data-equip-id="${equip.aten_equip_id}" data-aten-id="${atenId}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
                <td>${e(equip.aten_equip_descricao)}</td>
            </tr>
        `;
        tbody.append(row);
    });
}

function e(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

$(document).on("click", ".btn-delete-equip", function () {
    const equipId = $(this).data("equip-id");
    const atenId = $(this).data("aten-id");

    $.ajax({
        url: baseURL + "/atendimentos/" + atenId + "/equipamentos/" + equipId,
        type: "DELETE",
        dataType: "json",
        headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
        success: function (response) {
            carregarEquipamentos(atenId);
            showNotification("fas fa-check-double", response.message, "success", 2000);
        },
        error: function (xhr) {
            console.error("Erro ao remover equipamento:", xhr.status, xhr.responseText);
            showNotification(
                "fas fa-bug",
                "Erro ao remover equipamento. Código: " + xhr.status,
                "danger",
                5000
            );
        }
    });
});