// atendimentos.relatorios.js

$(document).ready(function () {
    try {
        configDataTableAtendimentosRelatorios();
    } catch (e) {
        console.error("Erro ao inicializar DataTable:", e);
    }

    initSubmitRelatorio();
    initAtualizarRelatorio();
    initMaoObraTab();
    initEquipTab();
    initAtividadesTab();
    initOcorrenciasTab();
    initComentariosTab();
    initAssinaturasTab();

    $("#btnNovoRelatorio").on("click", function () {
        const hoje = new Date();
        const hojeISO = hoje.getFullYear() + '-'
            + String(hoje.getMonth() + 1).padStart(2, '0') + '-'
            + String(hoje.getDate()).padStart(2, '0');

        $("#rel_aten_id").val("");
        $("#rel_aten_label").val("");
        $("#rel_data").val(hojeISO);

        $("#modal_relatorio").modal({
            backdrop: "static",
            keyboard: false
        });
    });

    if ($.ui && $.ui.autocomplete && $('#rel_aten_label').length) {
        setupAutocomplete(
            "#rel_aten_label",
            "#rel_aten_id",
            baseURL + "/atendimentos-relatorios/autocomplete"
        );
    }

    $("#modal_relatorio").on("shown.bs.modal", function () {
        $("#rel_aten_label").focus();
    });

    $("#modal_relatorio").on("hidden.bs.modal", function () {
        $("#form_relatorio button[type='submit']").prop("disabled", false);
    });

    const relatorioId = getRelatorioIdAtual();

    if (relatorioId) {
        getData(relatorioId, 'tab-dados');
        getData(relatorioId, 'tab-horarios');
        getData(relatorioId, 'tab-clima');
        getData(relatorioId, 'tab-assinatura');
    }

    $(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function (e) {
        const relatorioId = getRelatorioIdAtual();
        const target = $(e.target).attr('href')?.replace('#', '');
        if (relatorioId && target === 'tab-assinatura') {
            getData(relatorioId, 'tab-assinatura');
        }
    });

    $(document).on('click', '.upload-trigger', function () {
        const inputId = $(this).data('input-id');
        const input = $(`#${inputId}`);
        if (input.length) {
            input.trigger('click');
        }
    });

    $(document).on('change', '.file-upload-input', function () {
        const files = this.files;
        const label = $(this).closest('.file-upload-group').find('.file-upload-text');

        if (files && files.length) {
            const names = Array.from(files).map(file => file.name).join(', ');
            label.text(names);
        } else {
            label.text('Nenhum arquivo selecionado');
        }
    });
});

function configDataTableAtendimentosRelatorios() {
    const tableEl = $("#dataTableAtendimentosRelatorios");
    if (!tableEl.length) return;

    if ($.fn.DataTable.isDataTable(tableEl)) {
        return tableEl.DataTable();
    }

    const url = tableEl.data("url");

    return tableEl.DataTable({
        ajax: {
            url: url,
            type: "GET",
            dataSrc: "data",
            error: function (xhr) {
                console.error(
                    "DataTables AJAX error:",
                    xhr.status,
                    xhr.responseText
                );

                showNotification(
                    "fas fa-bug",
                    "Erro ao carregar dados. Código: " + xhr.status,
                    "danger",
                    5000
                );
            }
        },
        columns: [
            { data: "acoes" },
            { data: "data" },
            { data: "obra" },
            { data: "natureza" },
            { data: "setor" },
            { data: "status" }
        ],
        columnDefs: [{ width: "6%", targets: 0 }],
        createdRow: function (row) {
            $("td", row).eq(0).addClass("text-center");
        }
    });
}

function handleAjaxError(xhr) {
    if (xhr.status === 422) {
        const errors = xhr.responseJSON?.errors || {};
        let msg = "<ul>";

        Object.values(errors).flat().forEach(m => {
            msg += `<li>${m}</li>`;
        });

        msg += "</ul>";

        showNotification(
            "fas fa-bug",
            "Ocorreram erros:<br>" + msg,
            "danger",
            5000
        );
    } else {
        showNotification(
            "fas fa-bug",
            "Ops... um erro inesperado ocorreu!",
            "danger",
            5000
        );
    }
}

function initSubmitRelatorio() {
    $(document).off("submit", "#form_relatorio").on("submit", "#form_relatorio", function (event) {
        event.preventDefault();

        const form = $(this);
        const actionUrl = baseURL + "/atendimentos-relatorios";
        const formData = form.serialize();

        const btnSubmit = form.find("button[type='submit']");
        btnSubmit.prop("disabled", true);

        $.ajax({
            url: actionUrl,
            type: "POST",
            data: formData,
            dataType: "json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            },
            success: function (response) {
                $("#modal_relatorio").modal("hide");

                $("#dataTableAtendimentosRelatorios")
                    .DataTable()
                    .ajax.reload(null, false);

                showNotification(
                    "fas fa-check-double",
                    response.message,
                    "success",
                    2000
                );

                btnSubmit.prop("disabled", false);
            },
            error: function (xhr) {
                btnSubmit.prop("disabled", false);
                handleAjaxError(xhr);
            }
        });
    });
}

function initAtualizarRelatorio() {
    $(document).off('click', '#btnAtualizarRelatorio').on('click', '#btnAtualizarRelatorio', function () {

        const abaAtiva = $('.tab-pane.active.show').attr('id');
        let form = null;

        switch (abaAtiva) {
            case 'tab-dados':
                form = $('#form_relatorio_dados');
                break;

            case 'tab-horarios':
                form = $('#form_relatorio_horarios');
                break;

            case 'tab-clima':
                form = $('#form_relatorio_clima');
                break;

            case 'tab-assinatura':
                form = $('#form_relatorio_assinaturas');
                break;

            case 'tab-anexos':
                // handled specially below (no HTML form); mark form as a dummy jQuery object
                form = $();
                break;

            default:
                showNotification(
                    'fas fa-exclamation-triangle',
                    'Nenhuma ação definida para esta aba.',
                    'warning',
                    3000
                );
                return;
        }

        if (abaAtiva === 'tab-assinatura') {
            const relatorioId = getRelatorioIdAtual();
            if (!relatorioId) {
                showNotification('fas fa-exclamation-triangle', 'Salve o relatório antes de atualizar as assinaturas.', 'warning', 3500);
                return;
            }

            const status = $('#form_relatorio_assinaturas input[name="aten_rel_status"]:checked').val();
            const assinaturaResponsavel = window.signatureData?.responsavel || '';
            const assinaturaCliente = window.signatureData?.cliente || '';

            $.ajax({
                url: baseURL + `/atendimentos-relatorios/${relatorioId}/assinaturas`,
                type: 'POST',
                data: {
                    aten_rel_status: status,
                    assinatura_responsavel: assinaturaResponsavel,
                    assinatura_cliente: assinaturaCliente,
                },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    showNotification('fas fa-check', response.message || 'Assinaturas atualizadas.', 'success', 3000);
                    if (response.data?.assinaturas) {
                        if (response.data.assinaturas.responsavel) {
                            const urlResp = response.data.assinaturas.responsavel + '?v=' + Date.now();
                            $('#assinaturaResponsavelPreview').html(`<img src="${urlResp}" class="img-fluid border" alt="Assinatura Responsável">`);
                        }
                        if (response.data.assinaturas.cliente) {
                            const urlCli = response.data.assinaturas.cliente + '?v=' + Date.now();
                            $('#assinaturaClientePreview').html(`<img src="${urlCli}" class="img-fluid border" alt="Assinatura Cliente">`);
                        }
                    }
                    window.signatureData = {};
                },
                error: function (xhr) {
                    handleAjaxError(xhr);
                }
            });

            return;
        }

        if (abaAtiva === 'tab-anexos') {
            const relatorioId = getRelatorioIdAtual();
            if (!relatorioId) {
                showNotification('fas fa-exclamation-triangle', 'Salve o relatório antes de enviar anexos.', 'warning', 3500);
                return;
            }

            const fd = new FormData();
            const arquivos = document.getElementById('uploadArquivosInput');
            const fotos = document.getElementById('uploadFotosInput');
            const videos = document.getElementById('uploadVideosInput');

            if (arquivos && arquivos.files.length) {
                Array.from(arquivos.files).forEach(f => fd.append('arquivos[]', f));
            }
            if (fotos && fotos.files.length) {
                Array.from(fotos.files).forEach(f => fd.append('fotos[]', f));
            }
            if (videos && videos.files.length) {
                Array.from(videos.files).forEach(f => fd.append('videos[]', f));
            }

            $.ajax({
                url: baseURL + `/atendimentos-relatorios/${relatorioId}/upload-anexos`,
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    showNotification('fas fa-check', response.message || 'Uploads concluídos.', 'success', 3000);

                    // clear file inputs and labels to avoid duplicate uploads
                    ['uploadArquivosInput','uploadFotosInput','uploadVideosInput'].forEach(function(id){
                        const inp = document.getElementById(id);
                        if (inp) {
                            inp.value = '';
                            const lbl = $(inp).closest('.file-upload-group').find('.file-upload-text');
                            if (lbl && lbl.length) lbl.text('Nenhum arquivo selecionado');
                        }
                    });

                    refreshAnexos(relatorioId);
                },
                error: function (xhr) {
                    handleAjaxError(xhr);
                }
            });

            return;
        }

        if (!form || !form.length) {
            showNotification(
                'fas fa-exclamation-triangle',
                'Formulário não encontrado para esta aba.',
                'warning',
                3000
            );
            return;
        }

        const actionUrl = form.data('action');
        const formData = form.serialize();

        $.ajax({
            url: actionUrl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                showNotification(
                    'fas fa-check',
                    response.message,
                    'success',
                    2000
                );

                const abaId = $('.tab-pane.active.show').attr('id');
                const relatorioId = $('#form_relatorio_dados').data('action').match(/\d+/)[0];

                getData(relatorioId, abaId);
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};
                    let msg = '<ul>';

                    Object.values(errors).flat().forEach(function (m) {
                        msg += `<li>${m}</li>`;
                    });

                    msg += '</ul>';

                    showNotification(
                        'fas fa-bug',
                        'Ocorreram erros:<br>' + msg,
                        'danger',
                        5000
                    );
                } else {
                    showNotification(
                        'fas fa-bug',
                        'Erro ao salvar os dados.',
                        'danger',
                        5000
                    );
                }
            }
        });
    });
}

function refreshAnexos(relatorioId) {
    $.ajax({
        url: baseURL + `/atendimentos-relatorios/${relatorioId}/anexos`,
        type: 'GET',
        dataType: 'json',
        success: function (response) {
            renderAnexos(response);
        },
        error: function (xhr) {
            handleAjaxError(xhr);
        }
    });
}

function renderAnexos(data) {
    const arquivosList = $('#anexosArquivosList');
    arquivosList.empty();
    if (data.arquivos && data.arquivos.length) {
        const ul = $('<ul class="list-unstyled"></ul>');
        data.arquivos.forEach(function (item) {
            ul.append(`<li class="d-flex align-items-center justify-content-between mb-1"><a href="${item.url}" target="_blank">${item.name}</a><button type="button" class="btn btn-sm btn-outline-danger btn-delete-anexo" data-type="arquivo" data-id="${item.id}" aria-label="Excluir anexo"><i class="fas fa-trash"></i></button></li>`);
        });
        arquivosList.append('<h6>Anexos</h6>').append(ul);
    }

    const fotosContainer = $('#anexosFotosContainer');
    fotosContainer.empty();
    if (data.fotos && data.fotos.length) {
        data.fotos.forEach(function (item) {
            fotosContainer.append(`<div class="m-1 position-relative" style="width:120px;"><a href="#" class="anexo-thumb" data-type="image" data-src="${item.url}"><img src="${item.thumb_url}" style="width:120px;height:80px;object-fit:cover;border-radius:.35rem;" alt="foto"></a><button type="button" class="btn btn-sm btn-danger btn-delete-anexo position-absolute" style="top:4px;right:4px;" data-type="foto" data-id="${item.id}" aria-label="Excluir foto"><i class="fas fa-trash"></i></button></div>`);
        });
    }

    const videosContainer = $('#anexosVideosContainer');
    videosContainer.empty();
    if (data.videos && data.videos.length) {
        data.videos.forEach(function (item) {
            videosContainer.append(`<div class="m-1 position-relative" style="width:160px;"><a href="#" class="anexo-thumb" data-type="video" data-src="${item.url}"><img src="${item.thumb_url}" style="width:160px;height:90px;object-fit:cover;border-radius:.35rem;" alt="video"></a><button type="button" class="btn btn-sm btn-danger btn-delete-anexo position-absolute" style="top:4px;right:4px;" data-type="video" data-id="${item.id}" aria-label="Excluir vídeo"><i class="fas fa-trash"></i></button></div>`);
        });
    }
}

$(document).on('click', '.btn-delete-anexo', function (e) {
    e.preventDefault();
    const type = $(this).data('type');
    const itemId = $(this).data('id');
    const relatorioId = getRelatorioIdAtual();

    if (!relatorioId) {
        showNotification('fas fa-exclamation-triangle', 'Salve o relatório antes de excluir um anexo.', 'warning', 3500);
        return;
    }

    if (!confirm('Deseja realmente excluir este anexo?')) {
        return;
    }

    $.ajax({
        url: baseURL + `/atendimentos-relatorios/${relatorioId}/anexos/${type}/${itemId}`,
        type: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            showNotification('fas fa-check', response.message || 'Anexo excluído.', 'success', 3000);
            refreshAnexos(relatorioId);
        },
        error: function (xhr) {
            handleAjaxError(xhr);
        }
    });
});

function initMaoObraTab() {
    if ($.ui && $.ui.autocomplete && $('#mao_obra_label').length) {
        setupAutocomplete(
            '#mao_obra_label',
            '#mao_obra_id',
            baseURL + '/mao-de-obra/autocomplete',
            function (item) {
                $('#mao_obra_tp_id').val(item.tp_id || '');
                $('#mao_obra_label').data('selectedItem', item);
            }
        );
    }

    $(document).off('input blur', '#mao_obra_qtd').on('input blur', '#mao_obra_qtd', function () {
        const v = parseInt($(this).val(), 10);
        if (isNaN(v) || v < 1) {
            $(this).val(1);
        }
    });

    $(document).off('change', '#mao_obra_label').on('change', '#mao_obra_label', function () {
        if (!$('#mao_obra_id').val()) {
            $('#mao_obra_tp_id').val('');
            $('#mao_obra_label').removeData('selectedItem');
        }
    });

    $(document).off('click', '#btnAddMaoObra').on('click', '#btnAddMaoObra', function () {

        const relatorioId = getRelatorioIdAtual();

        if (!relatorioId) {
            showNotification(
                'fas fa-exclamation-triangle',
                'Salve o relatório antes de adicionar mão de obra.',
                'warning',
                3500
            );
            return;
        }

        const ocupId = $('#mao_obra_id').val();
        const tpId = $('#mao_obra_tp_id').val();
        const qtd = parseInt($('#mao_obra_qtd').val(), 10);

        if (!ocupId || !tpId) {
            showNotification(
                'fas fa-exclamation-triangle',
                'Selecione uma mão de obra válida na lista.',
                'warning',
                3000
            );
            return;
        }

        if (isNaN(qtd) || qtd < 1) {
            showNotification(
                'fas fa-exclamation-triangle',
                'Quantidade deve ser no mínimo 1.',
                'warning',
                3000
            );
            return;
        }

        const key = `${tpId}-${ocupId}`;
        if ($(`#tableMaoObra tbody tr[data-key="${key}"]`).length) {
            showNotification(
                'fas fa-exclamation-triangle',
                'Essa mão de obra já foi adicionada.',
                'warning',
                3000
            );
            return;
        }

        const btn = $('#btnAddMaoObra');
        btn.prop('disabled', true);

        $.ajax({
            url: baseURL + `/atendimentos-relatorios/${relatorioId}/mao-de-obra`,
            type: 'POST',
            dataType: 'json',
            data: {
                ocup_id: ocupId,
                qtd: qtd
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                const d = response.data;

                const tr = $(`
                        <tr data-key="${d.tp_id}-${d.ocup_id}"
                            data-ocup-id="${d.ocup_id}"
                            style="display:none;">
                            <td>
                                <button type="button"
                                    class="btn btn-danger btn-sm btn-icon-split btnRemoveMaoObra">
                                    <span class="icon text-white-50">
                                        <i class="fas fa-trash"></i>
                                    </span>
                                    <span class="text">Excluir</span>
                                </button>
                            </td>
                            <td>${escapeHtml(d.tp_label)}</td>
                            <td>${escapeHtml(d.ocup)}</td>
                            <td>${d.qtd}</td>
                        </tr>
                    `);

                $('#tableMaoObra tbody').append(tr);
                tr.fadeIn(500);

                showNotification(
                    'fas fa-check',
                    response.message,
                    'success',
                    2000
                );

                $('#mao_obra_label').val('').removeData('selectedItem');
                $('#mao_obra_id').val('');
                $('#mao_obra_tp_id').val('');
                $('#mao_obra_qtd').val(1);
                $('#mao_obra_label').focus();
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const msg = xhr.responseJSON?.message || 'Erro de validação.';
                    showNotification('fas fa-bug', msg, 'danger', 4000);
                } else {
                    handleAjaxError(xhr);
                }
            },
            complete: function () {
                btn.prop('disabled', false);
            }
        });
    });

    $(document).off('click', '.btnRemoveMaoObra').on('click', '.btnRemoveMaoObra', function () {

        const relatorioId = getRelatorioIdAtual();
        const tr = $(this).closest('tr');
        const ocupId = tr.data('ocup-id');

        if (!relatorioId || !ocupId) {
            tr.fadeOut(500, function () { tr.remove(); });
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true);

        $.ajax({
            url: baseURL + `/atendimentos-relatorios/${relatorioId}/mao-de-obra/${ocupId}`,
            type: 'DELETE',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                tr.fadeOut(500, function () {
                    tr.remove();
                });

                showNotification(
                    'fas fa-check',
                    response.message,
                    'success',
                    2000
                );
            },
            error: function (xhr) {
                btn.prop('disabled', false);
                handleAjaxError(xhr);
            }
        });
    });
}

function initEquipTab() {
    if ($.ui && $.ui.autocomplete && $('#equip_label').length) {
        setupAutocomplete(
            '#equip_label',
            '#equip_id',
            baseURL + '/equipamentos/autocomplete',
            function (item) {
                $('#equip_label').data('selectedItem', item);
            }
        );
    }

    $(document).off('input blur', '#equip_qtd').on('input blur', '#equip_qtd', function () {
        const v = parseInt($(this).val(), 10);
        if (isNaN(v) || v < 1) $(this).val(1);
    });

    $(document).off('change', '#equip_label').on('change', '#equip_label', function () {
        if (!$('#equip_id').val()) {
            $('#equip_label').removeData('selectedItem');
        }
    });

    $(document).off('click', '#btnAddEquip').on('click', '#btnAddEquip', function () {
        const relatorioId = getRelatorioIdAtual();

        if (!relatorioId) {
            showNotification('fas fa-exclamation-triangle', 'Salve o relatório antes de adicionar equipamento.', 'warning', 3500);
            return;
        }

        const equipId = $('#equip_id').val();
        const qtd = parseInt($('#equip_qtd').val(), 10);

        if (!equipId) {
            showNotification('fas fa-exclamation-triangle', 'Selecione um equipamento válido na lista.', 'warning', 3000);
            return;
        }
        if (isNaN(qtd) || qtd < 1) {
            showNotification('fas fa-exclamation-triangle', 'Quantidade deve ser no mínimo 1.', 'warning', 3000);
            return;
        }

        const key = `equip-${equipId}`;
        if ($(`#tableEquip tbody tr[data-key="${key}"]`).length) {
            showNotification('fas fa-exclamation-triangle', 'Esse equipamento já foi adicionado.', 'warning', 3000);
            return;
        }

        const btn = $('#btnAddEquip');
        btn.prop('disabled', true);

        $.ajax({
            url: baseURL + `/atendimentos-relatorios/${relatorioId}/equipamentos`,
            type: 'POST',
            dataType: 'json',
            data: { equip_id: equipId, qtd: qtd },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                const d = response.data;

                const tr = $(`
                    <tr data-key="equip-${d.equip_id}"
                        data-equip-id="${d.equip_id}"
                        style="display:none;">
                        <td>
                            <button type="button" class="btn btn-danger btn-sm btn-icon-split btnRemoveEquip">
                                <span class="icon text-white-50"><i class="fas fa-trash"></i></span>
                                <span class="text">Excluir</span>
                            </button>
                        </td>
                        <td>${escapeHtml(d.equip)}</td>
                        <td>${d.qtd}</td>
                    </tr>
                `);

                $('#tableEquip tbody').append(tr);
                tr.fadeIn(500);

                showNotification('fas fa-check', response.message, 'success', 2000);

                $('#equip_label').val('').removeData('selectedItem');
                $('#equip_id').val('');
                $('#equip_qtd').val(1);
                $('#equip_label').focus();
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const msg = xhr.responseJSON?.message || 'Erro de validação.';
                    showNotification('fas fa-bug', msg, 'danger', 4000);
                } else {
                    handleAjaxError(xhr);
                }
            },
            complete: function () {
                btn.prop('disabled', false);
            }
        });
    });

    $(document).off('click', '.btnRemoveEquip').on('click', '.btnRemoveEquip', function () {
        const relatorioId = getRelatorioIdAtual();
        const tr = $(this).closest('tr');
        const equipId = tr.data('equip-id');

        if (!relatorioId || !equipId) {
            tr.fadeOut(500, function () { tr.remove(); });
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true);

        $.ajax({
            url: baseURL + `/atendimentos-relatorios/${relatorioId}/equipamentos/${equipId}`,
            type: 'DELETE',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                tr.fadeOut(500, function () { tr.remove(); });
                showNotification('fas fa-check', response.message, 'success', 2000);
            },
            error: function (xhr) {
                btn.prop('disabled', false);
                handleAjaxError(xhr);
            }
        });
    });
}

function initAtividadesTab() {

    $(document).off('click', '#btnNovaAtividade').on('click', '#btnNovaAtividade', function () {
        $('#aten_rel_ativ_id').val('');
        $('#aten_rel_ativ_descricao').val('');
        $('#aten_rel_ativ_status').val('0');

        $('#modalAtividade').modal({
            backdrop: 'static',
            keyboard: false
        });

        setTimeout(() => $('#aten_rel_ativ_descricao').focus(), 200);
    });

    $(document).off('hidden.bs.modal', '#modalAtividade').on('hidden.bs.modal', '#modalAtividade', function () {
        $('#aten_rel_ativ_id').val('');
        $('#aten_rel_ativ_descricao').val('');
        $('#aten_rel_ativ_status').val('0');
        $('#btnSalvarAtividade').prop('disabled', false);
    });

    $(document).off('submit', '#formAtividade').on('submit', '#formAtividade', function (e) {
        e.preventDefault();

        const relatorioId = getRelatorioIdAtual();

        if (!relatorioId) {
            showNotification(
                'fas fa-exclamation-triangle',
                'Salve o relatório antes de adicionar atividades.',
                'warning',
                3500
            );
            return;
        }

        const ativId = $('#aten_rel_ativ_id').val();

        const payload = {
            aten_rel_ativ_descricao: $('#aten_rel_ativ_descricao').val(),
            aten_rel_ativ_status: $('#aten_rel_ativ_status').val()
        };

        const url = ativId
            ? baseURL + `/atendimentos-relatorios/${relatorioId}/atividades/${ativId}`
            : baseURL + `/atendimentos-relatorios/${relatorioId}/atividades`;

        const btn = $('#btnSalvarAtividade');
        btn.prop('disabled', true);

        $.ajax({
            url: url,
            type: 'POST',
            data: payload,
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {

                if (!ativId && response.data) {
                    const tr = buildAtividadeRow(response.data).hide();
                    $('#tableAtividades tbody').append(tr);
                    tr.fadeIn(500);
                } else if (ativId && response.data) {
                    const tr = $(`#tableAtividades tbody tr[data-ativ-id="${response.data.id}"]`);

                    if (tr.length) {
                        tr.find('.td-descricao').html(escapeHtml(response.data.descricao));
                        tr.find('.td-status').html(renderStatusAtividade(response.data.status));
                        tr.data('descricao', response.data.descricao);
                        tr.data('status', response.data.status);
                    } else {
                        const relId = getRelatorioIdAtual();
                        if (relId) {
                            getData(relId, 'tab-dados');
                        }
                    }
                }

                $('#modalAtividade').modal('hide');

                showNotification(
                    'fas fa-check',
                    response.message,
                    'success',
                    2000
                );
            },
            error: function (xhr) {
                handleAjaxError(xhr);
            },
            complete: function () {
                btn.prop('disabled', false);
            }
        });
    });

    $(document).off('click', '.btnEditAtividade').on('click', '.btnEditAtividade', function () {
        const tr = $(this).closest('tr');

        $('#aten_rel_ativ_id').val(tr.data('ativ-id'));
        $('#aten_rel_ativ_descricao').val(tr.data('descricao'));
        $('#aten_rel_ativ_status').val(tr.data('status'));

        $('#modalAtividade').modal({
            backdrop: 'static',
            keyboard: false
        });

        setTimeout(() => $('#aten_rel_ativ_descricao').focus(), 200);
    });

    $(document).off('click', '.btnDelAtividade').on('click', '.btnDelAtividade', function () {
        const relatorioId = getRelatorioIdAtual();
        const tr = $(this).closest('tr');
        const ativId = tr.data('ativ-id');

        if (!relatorioId || !ativId) {
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true);

        $.ajax({
            url: baseURL + `/atendimentos-relatorios/${relatorioId}/atividades/${ativId}`,
            type: 'DELETE',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                tr.fadeOut(500, function () {
                    tr.remove();
                });

                showNotification(
                    'fas fa-check',
                    response.message,
                    'success',
                    2000
                );
            },
            error: function (xhr) {
                btn.prop('disabled', false);
                handleAjaxError(xhr);
            }
        });
    });
}

function initOcorrenciasTab() {
    if ($.ui && $.ui.autocomplete && $('#ocorrencia_label').length) {
        setupAutocomplete(
            '#ocorrencia_label',
            '#ocorrencia_id',
            baseURL + '/ocorrencias/autocomplete',
            function (item) {
                $('#ocorrencia_label').data('selectedItem', item);
            }
        );
    }

    $(document).off('change', '#ocorrencia_label').on('change', '#ocorrencia_label', function () {
        if (!$('#ocorrencia_id').val()) {
            $('#ocorrencia_label').removeData('selectedItem');
        }
    });

    $(document).off('click', '#btnAddOcorrencia').on('click', '#btnAddOcorrencia', function () {
        const relatorioId = getRelatorioIdAtual();

        if (!relatorioId) {
            showNotification(
                'fas fa-exclamation-triangle',
                'Salve o relatório antes de adicionar ocorrência.',
                'warning',
                3500
            );
            return;
        }

        const ocorrenciaId = $('#ocorrencia_id').val();
        const item = $('#ocorrencia_label').data('selectedItem');
        const observacao = $('#ocorrencia_observacao').val();

        if (!ocorrenciaId || !item) {
            showNotification(
                'fas fa-exclamation-triangle',
                'Selecione uma ocorrência válida na lista.',
                'warning',
                3000
            );
            return;
        }

        const key = `ocorr-${ocorrenciaId}`;
        if ($(`#tableOcorrencias tbody tr[data-key="${key}"]`).length) {
            showNotification(
                'fas fa-exclamation-triangle',
                'Essa ocorrência já foi adicionada.',
                'warning',
                3000
            );
            return;
        }

        const btn = $('#btnAddOcorrencia');
        btn.prop('disabled', true);

        $.ajax({
            url: baseURL + `/atendimentos-relatorios/${relatorioId}/ocorrencias`,
            type: 'POST',
            dataType: 'json',
            data: {
                ocorrencia_id: ocorrenciaId,
                observacao: observacao
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                const d = response.data;

                const tr = $(`
                    <tr data-key="ocorr-${d.ocorrencia_id}"
                        data-ocorrencia-id="${d.ocorrencia_id}"
                        style="display:none;">
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm btn-icon-split btnRemoveOcorrencia">
                                <span class="icon text-white-50">
                                    <i class="fas fa-trash"></i>
                                </span>
                                <span class="text">Excluir</span>
                            </button>
                        </td>
                        <td>${escapeHtml(d.ocorrencia)}</td>
                        <td>${escapeHtml(d.observacao || '')}</td>
                    </tr>
                `);

                $('#tableOcorrencias tbody').append(tr);
                tr.fadeIn(500);

                showNotification(
                    'fas fa-check',
                    response.message,
                    'success',
                    2000
                );

                $('#ocorrencia_label').val('').removeData('selectedItem');
                $('#ocorrencia_id').val('');
                $('#ocorrencia_observacao').val('');
                $('#ocorrencia_label').focus();
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const msg = xhr.responseJSON?.message || 'Erro de validação.';
                    showNotification('fas fa-bug', msg, 'danger', 4000);
                } else {
                    handleAjaxError(xhr);
                }
            },
            complete: function () {
                btn.prop('disabled', false);
            }
        });
    });

    $(document).off('click', '.btnRemoveOcorrencia').on('click', '.btnRemoveOcorrencia', function () {
        const relatorioId = getRelatorioIdAtual();
        const tr = $(this).closest('tr');
        const ocorrenciaId = tr.data('ocorrencia-id');

        if (!relatorioId || !ocorrenciaId) {
            tr.fadeOut(500, function () { tr.remove(); });
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true);

        $.ajax({
            url: baseURL + `/atendimentos-relatorios/${relatorioId}/ocorrencias/${ocorrenciaId}`,
            type: 'DELETE',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                tr.fadeOut(500, function () { tr.remove(); });

                showNotification(
                    'fas fa-check',
                    response.message,
                    'success',
                    2000
                );
            },
            error: function (xhr) {
                btn.prop('disabled', false);
                handleAjaxError(xhr);
            }
        });
    });
}

function initComentariosTab() {
    $(document).off('click', '#btnNovoComentario').on('click', '#btnNovoComentario', function () {
        $('#aten_rel_com_id').val('');
        $('#aten_rel_com_descricao').val('');

        $('#modalComentario').modal({
            backdrop: 'static',
            keyboard: false
        });

        setTimeout(() => $('#aten_rel_com_descricao').focus(), 200);
    });

    $(document).off('hidden.bs.modal', '#modalComentario').on('hidden.bs.modal', '#modalComentario', function () {
        $('#aten_rel_com_id').val('');
        $('#aten_rel_com_descricao').val('');
        $('#btnSalvarComentario').prop('disabled', false);
    });

    $(document).off('submit', '#formComentario').on('submit', '#formComentario', function (e) {
        e.preventDefault();

        const relatorioId = getRelatorioIdAtual();

        if (!relatorioId) {
            showNotification(
                'fas fa-exclamation-triangle',
                'Salve o relatório antes de adicionar comentários.',
                'warning',
                3500
            );
            return;
        }

        const comentarioId = $('#aten_rel_com_id').val();

        const payload = {
            aten_rel_com_descricao: $('#aten_rel_com_descricao').val()
        };

        const url = comentarioId
            ? baseURL + `/atendimentos-relatorios/${relatorioId}/comentarios/${comentarioId}`
            : baseURL + `/atendimentos-relatorios/${relatorioId}/comentarios`;

        const btn = $('#btnSalvarComentario');
        btn.prop('disabled', true);

        $.ajax({
            url: url,
            type: 'POST',
            data: payload,
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {

                if (!comentarioId && response.data) {
                    const tr = buildComentarioRow(response.data).hide();
                    $('#tableComentarios tbody').append(tr);
                    tr.fadeIn(500);
                } else if (comentarioId && response.data) {
                    const tr = $(`#tableComentarios tbody tr[data-com-id="${response.data.id}"]`);

                    if (tr.length) {
                        tr.find('.td-comentario').html(escapeHtml(response.data.descricao));
                        tr.data('descricao', response.data.descricao);
                    } else {
                        const relId = getRelatorioIdAtual();
                        if (relId) {
                            getData(relId, 'tab-dados');
                        }
                    }
                }

                $('#modalComentario').modal('hide');

                showNotification(
                    'fas fa-check',
                    response.message,
                    'success',
                    2000
                );
            },
            error: function (xhr) {
                handleAjaxError(xhr);
            },
            complete: function () {
                btn.prop('disabled', false);
            }
        });
    });

    $(document).off('click', '.btnEditComentario').on('click', '.btnEditComentario', function () {
        const tr = $(this).closest('tr');

        $('#aten_rel_com_id').val(tr.data('com-id'));
        $('#aten_rel_com_descricao').val(tr.data('descricao'));

        $('#modalComentario').modal({
            backdrop: 'static',
            keyboard: false
        });

        setTimeout(() => $('#aten_rel_com_descricao').focus(), 200);
    });

    $(document).off('click', '.btnDelComentario').on('click', '.btnDelComentario', function () {
        const relatorioId = getRelatorioIdAtual();
        const tr = $(this).closest('tr');
        const comentarioId = tr.data('com-id');

        if (!relatorioId || !comentarioId) {
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true);

        $.ajax({
            url: baseURL + `/atendimentos-relatorios/${relatorioId}/comentarios/${comentarioId}`,
            type: 'DELETE',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                tr.fadeOut(500, function () {
                    tr.remove();
                });

                showNotification(
                    'fas fa-check',
                    response.message,
                    'success',
                    2000
                );
            },
            error: function (xhr) {
                btn.prop('disabled', false);
                handleAjaxError(xhr);
            }
        });
    });
}

function buildAtividadeRow(a) {
    return $(`
        <tr data-ativ-id="${a.id}"
            data-descricao="${escapeHtml(a.descricao)}"
            data-status="${a.status}">
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-primary btnEditAtividade" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn btn-sm btn-danger btnDelAtividade" title="Excluir">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
            <td class="td-descricao">${escapeHtml(a.descricao)}</td>
            <td class="td-status">${renderStatusAtividade(a.status)}</td>
        </tr>
    `);
}

function buildComentarioRow(c) {
    return $(`
        <tr data-com-id="${c.id}"
            data-descricao="${escapeHtml(c.descricao)}">
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-primary btnEditComentario" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn btn-sm btn-danger btnDelComentario" title="Excluir">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
            <td class="td-comentario">${escapeHtml(c.descricao)}</td>
        </tr>
    `);
}

function getRelatorioIdAtual() {
    const byData = $('#btnAtualizarRelatorio').data('relatorio-id');
    if (byData) return byData;

    const action = $('#form_relatorio_dados').data('action');
    if (action) {
        const m = String(action).match(/\d+/);
        if (m) return m[0];
    }

    return null;
}

function getData(relatorioId, guia) {
    $.get(`/atendimentos-relatorios/${relatorioId}/get-data`, function (data) {

        switch (guia) {
            case 'tab-dados':
                applyDados(data.dados);
                break;

            case 'tab-horarios':
                applyHorarios(data.horarios);
                break;

            case 'tab-clima':
                applyClima(data.clima);
                break;

            case 'tab-assinatura':
                applyAssinaturas(data);
                break;
        }

        if (data.mao_obra) {
            applyMaoDeObra(data.mao_obra);
        }

        if (data.equipamentos) {
            applyEquipamentos(data.equipamentos);
        }

        if (data.atividades) {
            applyAtividades(data.atividades);
        }

        if (data.ocorrencias) {
            applyOcorrencias(data.ocorrencias);
        }

        if (data.comentarios) {
            applyComentarios(data.comentarios);
        }
    });
}

function applyDados(dados) {
    $('input[name="aten_rel_data"]').val(dados.aten_rel_data_iso);
    $('#tab-dados .dia-semana').text(dados.dia_semana);
    $('#tab-dados .prazo-total').text(dados.prazo_total + ' dias');
    $('#tab-dados .prazo-decorrido').text(dados.prazo_decorrido + ' dias');
    $('#tab-dados .prazo-vencer').text(dados.prazo_vencer + ' dias');
}

function applyHorarios(h) {
    $('input[name="aten_rel_hora_entrada"]').val(h.entrada?.substring(0, 5) || '');
    $('input[name="aten_rel_hora_inicio_intervalo"]').val(h.inicio_intervalo?.substring(0, 5) || '');
    $('input[name="aten_rel_hora_fim_intervalo"]').val(h.fim_intervalo?.substring(0, 5) || '');
    $('input[name="aten_rel_hora_saida"]').val(h.saida?.substring(0, 5) || '');
}

function applyClima(clima) {
    if (clima?.manha) {
        $(`#manha_${clima.manha}`).prop('checked', true);
    }
    if (clima?.tarde) {
        $(`#tarde_${clima.tarde}`).prop('checked', true);
    }
    if (clima?.noite) {
        $(`#noite_${clima.noite}`).prop('checked', true);
    }
}

function applyAssinaturas(data) {
    if (typeof data.status !== 'undefined') {
        $(`#form_relatorio_assinaturas input[name="aten_rel_status"][value="${data.status}"]`).prop('checked', true);
        $(`#form_relatorio_assinaturas .btn-group-toggle label`).removeClass('active');
        $(`#form_relatorio_assinaturas .btn-group-toggle input[name="aten_rel_status"]:checked`).closest('label').addClass('active');
    }

    if (data.assinaturas?.responsavel) {
        $('#assinaturaResponsavelPreview').html(`<img src="${data.assinaturas.responsavel}" class="img-fluid border" alt="Assinatura Responsável">`);
    }
    if (data.assinaturas?.cliente) {
        $('#assinaturaClientePreview').html(`<img src="${data.assinaturas.cliente}" class="img-fluid border" alt="Assinatura Cliente">`);
    }
}

function initAssinaturasTab() {
    window.signatureData = window.signatureData || {};

    function setupCanvas(canvasId) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return null;

        const ctx = canvas.getContext('2d');
        ctx.lineWidth = 2.2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#111';

        let drawing = false;
        let hasStroke = false;

        const getPoint = function (event) {
            const rect = canvas.getBoundingClientRect();
            const touch = event.touches ? event.touches[0] : event;
            return {
                x: touch.clientX - rect.left,
                y: touch.clientY - rect.top,
            };
        };

        const startDrawing = function (event) {
            event.preventDefault();
            drawing = true;
            hasStroke = true;
            const point = getPoint(event);
            ctx.beginPath();
            ctx.moveTo(point.x, point.y);
        };

        const draw = function (event) {
            if (!drawing) return;
            event.preventDefault();
            const point = getPoint(event);
            ctx.lineTo(point.x, point.y);
            ctx.stroke();
        };

        const endDrawing = function (event) {
            if (!drawing) return;
            event.preventDefault();
            drawing = false;
        };

        const clearCanvas = function () {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            canvas.style.background = '#fff';
            hasStroke = false;
        };

        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', endDrawing);
        canvas.addEventListener('mouseleave', endDrawing);
        canvas.addEventListener('touchstart', startDrawing);
        canvas.addEventListener('touchmove', draw);
        canvas.addEventListener('touchend', endDrawing);
        canvas.addEventListener('touchcancel', endDrawing);

        clearCanvas();

        return {
            canvas,
            clearCanvas,
            hasStroke: function () {
                return hasStroke;
            },
            getDataUrl: function () {
                return canvas.toDataURL('image/png');
            }
        };
    }

    const responsavelCanvas = setupCanvas('assinaturaResponsavelCanvas');
    const clienteCanvas = setupCanvas('assinaturaClienteCanvas');

    $(document).off('click', '.btn-clear-signature').on('click', '.btn-clear-signature', function () {
        const signatureType = $(this).data('signature');
        if (signatureType === 'responsavel' && responsavelCanvas) {
            responsavelCanvas.clearCanvas();
            delete window.signatureData.responsavel;
        }
        if (signatureType === 'cliente' && clienteCanvas) {
            clienteCanvas.clearCanvas();
            delete window.signatureData.cliente;
        }
    });

    $(document).off('click', '.btn-save-signature').on('click', '.btn-save-signature', function () {
        const signatureType = $(this).data('signature');
        const relatorioId = getRelatorioIdAtual();
        if (!relatorioId) {
            showNotification('fas fa-exclamation-triangle', 'Salve o relatório antes de registrar a assinatura.', 'warning', 3500);
            return;
        }

        let canvasObj = null;
        if (signatureType === 'responsavel') canvasObj = responsavelCanvas;
        if (signatureType === 'cliente') canvasObj = clienteCanvas;

        if (!canvasObj || !canvasObj.hasStroke()) {
            showNotification('fas fa-exclamation-triangle', 'Desenhe a assinatura antes de salvar.', 'warning', 3500);
            return;
        }

        const dataUrl = canvasObj.getDataUrl();
        window.signatureData = window.signatureData || {};
        window.signatureData[signatureType] = dataUrl;

        $.ajax({
            url: baseURL + `/atendimentos-relatorios/${relatorioId}/assinaturas`,
            type: 'POST',
            data: {
                aten_rel_status: $('#form_relatorio_assinaturas input[name="aten_rel_status"]:checked').val(),
                [`assinatura_${signatureType}`]: dataUrl,
            },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                showNotification('fas fa-check', 'Assinatura salva com sucesso.', 'success', 3000);
                if (canvasObj) {
                    canvasObj.clearCanvas();
                }
                if (response.data?.assinaturas && response.data.assinaturas[signatureType]) {
                    const previewId = signatureType === 'responsavel' ? '#assinaturaResponsavelPreview' : '#assinaturaClientePreview';
                    $(previewId).html(`<img src="${response.data.assinaturas[signatureType]}" class="img-fluid border" alt="Assinatura ${signatureType}">`);
                    delete window.signatureData[signatureType];
                }
            },
            error: function (xhr) {
                handleAjaxError(xhr);
            }
        });
    });
}

function applyMaoDeObra(lista) {
    const tbody = $('#tableMaoObra tbody');

    tbody.empty();

    if (!Array.isArray(lista) || !lista.length) {
        return;
    }

    lista.forEach(function (d) {
        const key = `${d.tp_id}-${d.ocup_id}`;

        const tr = $(`
            <tr data-key="${key}"
                data-ocup-id="${d.ocup_id}"
                style="display:none;">
                <td>
                    <button type="button"
                        class="btn btn-danger btn-sm btn-icon-split btnRemoveMaoObra">
                        <span class="icon text-white-50">
                            <i class="fas fa-trash"></i>
                        </span>
                        <span class="text">Excluir</span>
                    </button>
                </td>
                <td>${escapeHtml(d.tp_label)}</td>
                <td>${escapeHtml(d.ocup)}</td>
                <td>${d.qtd}</td>
            </tr>
        `);

        tbody.append(tr);
        tr.fadeIn(300);
    });
}

function applyEquipamentos(lista) {
    const tbody = $('#tableEquip tbody');
    tbody.empty();

    if (!Array.isArray(lista) || !lista.length) return;

    lista.forEach(function (d) {
        const tr = $(`
            <tr data-key="equip-${d.equip_id}"
                data-equip-id="${d.equip_id}"
                style="display:none;">
                <td>
                    <button type="button" class="btn btn-danger btn-sm btn-icon-split btnRemoveEquip">
                        <span class="icon text-white-50"><i class="fas fa-trash"></i></span>
                        <span class="text">Excluir</span>
                    </button>
                </td>
                <td>${escapeHtml(d.equip)}</td>
                <td>${d.qtd}</td>
            </tr>
        `);

        tbody.append(tr);
        tr.fadeIn(300);
    });
}

function applyAtividades(lista) {
    const tbody = $('#tableAtividades tbody');
    tbody.empty();

    if (!Array.isArray(lista) || !lista.length) {
        return;
    }

    lista.forEach(function (a) {
        const tr = buildAtividadeRow(a).hide();
        tbody.append(tr);
        tr.fadeIn(300);
    });
}

function applyOcorrencias(lista) {
    const tbody = $('#tableOcorrencias tbody');
    tbody.empty();

    if (!Array.isArray(lista) || !lista.length) {
        return;
    }

    lista.forEach(function (d) {
        const tr = $(`
            <tr data-key="ocorr-${d.ocorrencia_id}"
                data-ocorrencia-id="${d.ocorrencia_id}"
                style="display:none;">
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm btn-icon-split btnRemoveOcorrencia">
                        <span class="icon text-white-50">
                            <i class="fas fa-trash"></i>
                        </span>
                        <span class="text">Excluir</span>
                    </button>
                </td>
                <td>${escapeHtml(d.ocorrencia)}</td>
                <td>${escapeHtml(d.observacao || '')}</td>
            </tr>
        `);

        tbody.append(tr);
        tr.fadeIn(300);
    });
}

function applyComentarios(lista) {
    const tbody = $('#tableComentarios tbody');
    tbody.empty();

    if (!Array.isArray(lista) || !lista.length) {
        return;
    }

    lista.forEach(function (c) {
        const tr = buildComentarioRow(c).hide();
        tbody.append(tr);
        tr.fadeIn(300);
    });
}

function renderStatusAtividade(status) {
    const map = {
        0: { text: 'Não iniciada', badge: 'secondary' },
        1: { text: 'Iniciada', badge: 'info' },
        2: { text: 'Em andamento', badge: 'primary' },
        3: { text: 'Concluída', badge: 'success' },
        4: { text: 'Paralisada', badge: 'warning' },
        5: { text: 'Não executada', badge: 'dark' }
    };

    const s = map[status] || { text: '-', badge: 'secondary' };
    return `<span class="badge badge-${s.badge}">${s.text}</span>`;
}