// atendimentos.relatorios.js

$(document).ready(function () {
    try {
        configDataTableAtendimentosRelatorios();
    } catch (e) {
        console.error("Erro ao inicializar DataTable:", e);
    }

    initSubmitRelatorio();
    initAtualizarRelatorio();
    initOcorrenciasTab();
    initServicosTab();
    initPecasTab();
    initDescricaoTab();
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

    initTextosTab();

    $(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function (e) {
        const rid = getRelatorioIdAtual();
        if (!rid) return;

        const target = $(e.target).attr('href')?.replace('#', '');

        switch (target) {
            case 'tab-dados':       carregarDados(rid);               break;
            case 'tab-horarios':    carregarHorarios(rid);            break;
            case 'tab-clima':       carregarClima(rid);               break;
            case 'tab-ocorrencias': carregarOcorrenciasRelatorio(rid); break;
            case 'tab-assinatura':  carregarAssinaturas(rid);         break;
            case 'tab-servicos':    carregarServicos(rid);            break;
            case 'tab-pecas':       carregarPecas(rid);               break;
            case 'tab-descricao':   carregarDescricaoItens(rid);      break;
            case 'tab-anexos':      refreshAnexos(rid);               break;
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
        serverSide: true,
        processing: true,
        stateSave: true,
        columns: [
            { data: "acoes" },
            { data: "data" },
            { data: "cliente" },
            { data: "nr_proposta" },
            { data: "natureza" },
            { data: "tecnico" },
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
        const resp   = xhr.responseJSON || {};
        const errors = resp.errors || {};
        const keys   = Object.keys(errors);

        let msg;
        if (keys.length) {
            msg = "Ocorreram erros:<br><ul>" +
                Object.values(errors).flat().map(m => `<li>${m}</li>`).join('') +
                "</ul>";
        } else {
            msg = resp.message || "Erro de validação.";
        }

        showNotification("fas fa-bug", msg, "danger", 5000);
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
                if (response.redirect_url) {
                    window.location.href = response.redirect_url;
                } else {
                    $("#modal_relatorio").modal("hide");
                    btnSubmit.prop("disabled", false);
                }
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

            case 'tab-info-adicionais':
                form = $('#form_informacoes_adicionais');
                break;

            case 'tab-servicos':
            case 'tab-pecas':
            case 'tab-descricao':
            case 'tab-ocorrencias':
                showNotification('fas fa-info-circle', 'Use os botões para adicionar e remover itens nesta aba.', 'info', 2500);
                return;

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

            // RF006: nome de quem assinou é obrigatório junto de cada assinatura nova.
            if (assinaturaResponsavel && !$('#assinatura_responsavel_nome').val().trim()) {
                showNotification('fas fa-exclamation-triangle', 'Informe o nome de quem assinou como Técnico.', 'warning', 3500);
                return;
            }
            if (assinaturaCliente && !$('#assinatura_cliente_nome').val().trim()) {
                showNotification('fas fa-exclamation-triangle', 'Informe o nome de quem assinou como Cliente.', 'warning', 3500);
                return;
            }

            $.ajax({
                url: baseURL + `/atendimentos-relatorios/${relatorioId}/assinaturas`,
                type: 'POST',
                data: {
                    aten_rel_status: status,
                    assinatura_responsavel: assinaturaResponsavel,
                    assinatura_responsavel_nome: $('#assinatura_responsavel_nome').val().trim(),
                    assinatura_responsavel_cpf: $('#assinatura_responsavel_cpf').val().trim(),
                    assinatura_cliente: assinaturaCliente,
                    assinatura_cliente_nome: $('#assinatura_cliente_nome').val().trim(),
                    assinatura_cliente_cpf: $('#assinatura_cliente_cpf').val().trim(),
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

        // Tabs de texto têm handler próprio via initTextosTab()
        const textTabForms = ['form_informacoes_adicionais'];
        if (textTabForms.includes(form.attr('id'))) {
            form.trigger('submit');
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

function initOcorrenciasTab() {
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

        const selectEl = $('#ocorrencia_id');
        const ocorrenciaId = selectEl.val();
        const observacao = $('#ocorrencia_observacao').val();

        if (!ocorrenciaId) {
            showNotification(
                'fas fa-exclamation-triangle',
                'Selecione uma ocorrência.',
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

                $('#ocorrencia_id').val('');
                $('#ocorrencia_observacao').val('');
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

function carregarDados(relatorioId) {
    $.get(baseURL + '/atendimentos-relatorios/' + relatorioId + '/dados', function (d) {
        $('input[name="aten_rel_data"]').val(d.aten_rel_data_iso);
        $('#tab-dados .dia-semana').text(d.dia_semana);
        $('#tab-dados .prazo-total').text(d.prazo_total + ' dias');
        $('#tab-dados .prazo-decorrido').text(d.prazo_decorrido + ' dias');
        $('#tab-dados .prazo-vencer').text(d.prazo_vencer + ' dias');
    });
}

function carregarHorarios(relatorioId) {
    $.get(baseURL + '/atendimentos-relatorios/' + relatorioId + '/horarios', function (h) {
        $('input[name="aten_rel_hora_entrada"]').val(h.entrada);
        $('input[name="aten_rel_hora_inicio_intervalo"]').val(h.inicio_intervalo);
        $('input[name="aten_rel_hora_fim_intervalo"]').val(h.fim_intervalo);
        $('input[name="aten_rel_hora_saida"]').val(h.saida);
    });
}

function carregarClima(relatorioId) {
    $.get(baseURL + '/atendimentos-relatorios/' + relatorioId + '/clima', function (clima) {
        if (clima?.manha) $(`#manha_${clima.manha}`).prop('checked', true);
        if (clima?.tarde) $(`#tarde_${clima.tarde}`).prop('checked', true);
        if (clima?.noite) $(`#noite_${clima.noite}`).prop('checked', true);
    });
}

function carregarOcorrenciasRelatorio(relatorioId) {
    $.get(baseURL + '/atendimentos-relatorios/' + relatorioId + '/ocorrencias', function (r) {
        applyOcorrencias(r.data);
    });
}

function carregarAssinaturas(relatorioId) {
    $.get(baseURL + '/atendimentos-relatorios/' + relatorioId + '/assinaturas', function (data) {
        if (data.assinaturas?.responsavel) {
            $('#assinaturaResponsavelPreview').html(`<img src="${data.assinaturas.responsavel}" class="img-fluid border" alt="Assinatura Responsável">`);
        }
        if (data.assinaturas?.cliente) {
            $('#assinaturaClientePreview').html(`<img src="${data.assinaturas.cliente}" class="img-fluid border" alt="Assinatura Cliente">`);
        }
    });
}

function initAssinaturasTab() {
    window.signatureData = window.signatureData || {};

    // RF006: máscara de CPF nos campos de quem assinou.
    $('#assinatura_responsavel_cpf, #assinatura_cliente_cpf').mask('000.000.000-00');

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

        // RF006: nome de quem assinou é obrigatório junto de cada assinatura nova.
        const nome = $(`#assinatura_${signatureType}_nome`).val().trim();
        if (!nome) {
            showNotification('fas fa-exclamation-triangle', 'Informe o nome de quem está assinando.', 'warning', 3500);
            return;
        }
        const cpf = $(`#assinatura_${signatureType}_cpf`).val().trim();

        const dataUrl = canvasObj.getDataUrl();
        window.signatureData = window.signatureData || {};
        window.signatureData[signatureType] = dataUrl;

        $.ajax({
            url: baseURL + `/atendimentos-relatorios/${relatorioId}/assinaturas`,
            type: 'POST',
            data: {
                aten_rel_status: $('#form_relatorio_assinaturas input[name="aten_rel_status"]:checked').val(),
                [`assinatura_${signatureType}`]: dataUrl,
                [`assinatura_${signatureType}_nome`]: nome,
                [`assinatura_${signatureType}_cpf`]: cpf,
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

function initTextosTab() {
    // form_descricao removido (RF001): itens de descrição agora salvam
    // individualmente via initDescricaoTab(), não por este form genérico.
    const mapa = {
        'form_informacoes_adicionais':{ campo: 'aten_rel_informacoes_adicionais', label: 'Observações Gerais' },
    };

    Object.keys(mapa).forEach(function (formId) {
        const config = mapa[formId];
        $(document).off('submit', '#' + formId).on('submit', '#' + formId, function (e) {
            e.preventDefault();
            const relatorioId = getRelatorioIdAtual();
            if (!relatorioId) {
                showNotification('fas fa-exclamation-triangle', 'Relatório não identificado.', 'warning', 3000);
                return;
            }

            const valor = $('#' + config.campo).val();
            $.ajax({
                url: baseURL + `/atendimentos-relatorios/${relatorioId}/texto/${config.campo}`,
                type: 'POST',
                data: { valor: valor },
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function () {
                    showNotification('fas fa-check-double', config.label + ' salvo com sucesso.', 'success', 2000);
                },
                error: function (xhr) {
                    handleAjaxError(xhr);
                }
            });
        });
    });
}

// ─── Serviços / Peças ─────────────────────────────────────────────────────────

function parseItemsJson(raw) {
    if (!raw) return [];
    try {
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed)) return parsed.filter(Boolean);
    } catch (e) {}
    return raw.split('\n').map(function (s) { return s.trim(); }).filter(Boolean);
}

function salvarItemsTexto(relatorioId, campo, items, callback) {
    $.ajax({
        url: baseURL + '/atendimentos-relatorios/' + relatorioId + '/texto/' + campo,
        type: 'POST',
        data: { valor: JSON.stringify(items) },
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function () {
            if (callback) callback();
        },
        error: function (xhr) {
            handleAjaxError(xhr);
        }
    });
}

function renderItemsTabela(items, tbodySelector, btnRemoveClass, label) {
    const tbody = $(tbodySelector);
    tbody.empty();
    if (!items.length) {
        tbody.append('<tr><td colspan="2" class="text-center text-muted">Nenhum ' + label + ' cadastrado.</td></tr>');
        return;
    }
    items.forEach(function (item, idx) {
        const tr = $('<tr style="display:none;">' +
            '<td class="text-center">' +
                '<button type="button" class="btn btn-danger btn-sm btn-icon-split ' + btnRemoveClass + '" data-idx="' + idx + '">' +
                    '<span class="icon text-white-50"><i class="fas fa-trash"></i></span>' +
                    '<span class="text">Excluir</span>' +
                '</button>' +
            '</td>' +
            '<td>' + escapeHtml(item) + '</td>' +
        '</tr>');
        tbody.append(tr);
        tr.fadeIn(300);
    });
}

function carregarServicos(relatorioId) {
    $.ajax({
        url: baseURL + '/atendimentos-relatorios/' + relatorioId + '/servicos',
        type: 'GET',
        dataType: 'json',
        success: function (r) { renderServicos(r.data); },
        error: function () { showNotification('fas fa-bug', 'Erro ao carregar serviços.', 'danger', 3000); }
    });
}

function renderServicos(items) {
    const tbody = $('#tableServicos tbody');
    tbody.empty();
    if (!items || !items.length) {
        tbody.append('<tr><td colspan="2" class="text-center text-muted">Nenhum serviço cadastrado.</td></tr>');
        return;
    }
    items.forEach(function (item) {
        const tr = $('<tr style="display:none;">' +
            '<td class="text-center">' +
                '<button type="button" class="btn btn-danger btn-sm btn-icon-split btnRemoveServico" data-id="' + item.aten_rel_serv_id + '">' +
                    '<span class="icon text-white-50"><i class="fas fa-trash"></i></span>' +
                    '<span class="text">Excluir</span>' +
                '</button>' +
            '</td>' +
            '<td>' + escapeHtml(item.aten_rel_serv_descricao) + '</td>' +
        '</tr>');
        tbody.append(tr);
        tr.fadeIn(300);
    });
}

function initServicosTab() {

    $(document).off('click', '#btnAddServico').on('click', '#btnAddServico', function () {
        const rid = getRelatorioIdAtual();
        if (!rid) {
            showNotification('fas fa-exclamation-triangle', 'Salve o relatório antes de adicionar serviços.', 'warning', 3500);
            return;
        }
        const descricao = $('#servico_descricao').val().trim();
        if (!descricao) {
            showNotification('fas fa-exclamation-triangle', 'Informe a descrição do serviço.', 'warning', 3000);
            return;
        }
        const btn = $(this);
        btn.prop('disabled', true);
        $.ajax({
            url: baseURL + '/atendimentos-relatorios/' + rid + '/servicos',
            type: 'POST',
            data: { descricao: descricao },
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (r) {
                $('#servico_descricao').val('').focus();
                carregarServicos(rid);
                showNotification('fas fa-check-double', r.message, 'success', 2000);
                btn.prop('disabled', false);
            },
            error: function (xhr) { btn.prop('disabled', false); handleAjaxError(xhr); }
        });
    });

    $(document).off('click', '.btnRemoveServico').on('click', '.btnRemoveServico', function () {
        const rid = getRelatorioIdAtual();
        const itemId = $(this).data('id');
        $.ajax({
            url: baseURL + '/atendimentos-relatorios/' + rid + '/servicos/' + itemId,
            type: 'DELETE',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (r) { carregarServicos(rid); showNotification('fas fa-check', r.message, 'success', 2000); },
            error: function (xhr) { handleAjaxError(xhr); }
        });
    });
}

function carregarPecas(relatorioId) {
    $.ajax({
        url: baseURL + '/atendimentos-relatorios/' + relatorioId + '/pecas',
        type: 'GET',
        dataType: 'json',
        success: function (r) { renderPecas(r.data); },
        error: function () { showNotification('fas fa-bug', 'Erro ao carregar peças.', 'danger', 3000); }
    });
}

function renderPecas(items) {
    const tbody = $('#tablePecas tbody');
    tbody.empty();
    if (!items || !items.length) {
        tbody.append('<tr><td colspan="2" class="text-center text-muted">Nenhuma peça cadastrada.</td></tr>');
        return;
    }
    items.forEach(function (item) {
        const tr = $('<tr style="display:none;">' +
            '<td class="text-center">' +
                '<button type="button" class="btn btn-danger btn-sm btn-icon-split btnRemovePeca" data-id="' + item.aten_rel_peca_id + '">' +
                    '<span class="icon text-white-50"><i class="fas fa-trash"></i></span>' +
                    '<span class="text">Excluir</span>' +
                '</button>' +
            '</td>' +
            '<td>' + escapeHtml(item.aten_rel_peca_descricao) + '</td>' +
        '</tr>');
        tbody.append(tr);
        tr.fadeIn(300);
    });
}

function initPecasTab() {

    $(document).off('click', '#btnAddPeca').on('click', '#btnAddPeca', function () {
        const rid = getRelatorioIdAtual();
        if (!rid) {
            showNotification('fas fa-exclamation-triangle', 'Salve o relatório antes de adicionar peças.', 'warning', 3500);
            return;
        }
        const descricao = $('#peca_descricao').val().trim();
        if (!descricao) {
            showNotification('fas fa-exclamation-triangle', 'Informe a descrição da peça.', 'warning', 3000);
            return;
        }
        const btn = $(this);
        btn.prop('disabled', true);
        $.ajax({
            url: baseURL + '/atendimentos-relatorios/' + rid + '/pecas',
            type: 'POST',
            data: { descricao: descricao },
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (r) {
                $('#peca_descricao').val('').focus();
                carregarPecas(rid);
                showNotification('fas fa-check-double', r.message, 'success', 2000);
                btn.prop('disabled', false);
            },
            error: function (xhr) { btn.prop('disabled', false); handleAjaxError(xhr); }
        });
    });

    $(document).off('click', '.btnRemovePeca').on('click', '.btnRemovePeca', function () {
        const rid = getRelatorioIdAtual();
        const itemId = $(this).data('id');
        $.ajax({
            url: baseURL + '/atendimentos-relatorios/' + rid + '/pecas/' + itemId,
            type: 'DELETE',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (r) { carregarPecas(rid); showNotification('fas fa-check', r.message, 'success', 2000); },
            error: function (xhr) { handleAjaxError(xhr); }
        });
    });
}

// ─── Descrição (itens: texto + foto opcional) — RF001 ──────────────────────────

function carregarDescricaoItens(relatorioId) {
    $.ajax({
        url: baseURL + '/atendimentos-relatorios/' + relatorioId + '/descricao-itens',
        type: 'GET',
        dataType: 'json',
        success: function (r) { renderDescricaoItens(r.data, r.legado); },
        error: function () { showNotification('fas fa-bug', 'Erro ao carregar itens de descrição.', 'danger', 3000); }
    });
}

function renderDescricaoItens(items, legado) {
    // RF001/RF004: retrocompatibilidade — relatório antigo (texto único) x
    // relatório novo (lista de itens), nunca os dois juntos na tela.
    const temLegado = !!(legado && legado.trim().length);
    $('#descricaoFormNovo').toggle(!temLegado);
    $('#descricaoLegadoBox').toggle(temLegado).text(legado || '');
    if (temLegado) {
        $('#listaDescricaoItens').empty();
        $('#descricaoItensVazio').hide();
        return;
    }

    const container = $('#listaDescricaoItens');
    container.empty();
    $('#descricaoItensVazio').toggle(!items || !items.length);

    (items || []).forEach(function (item) {
        // Mesmo modal de preview (#anexoPreviewModal) já usado na aba Anexos —
        // clicar na foto abre ela em tamanho maior.
        const fotoHtml = item.foto_url
            ? '<a href="#" class="anexo-thumb" data-type="image" data-src="' + item.foto_url + '">' +
                '<img src="' + item.foto_url + '" class="card-img-top" style="max-height:220px; object-fit:cover;" alt="Foto do item">' +
              '</a>'
            : '';
        const card = $(
            '<div class="col-md-4 mb-3" style="display:none;">' +
                '<div class="card h-100">' +
                    fotoHtml +
                    '<div class="card-body">' +
                        '<p class="card-text" style="white-space:pre-wrap;">' + escapeHtml(item.texto) + '</p>' +
                    '</div>' +
                    '<div class="card-footer text-right">' +
                        '<button type="button" class="btn btn-danger btn-sm btnRemoveDescricaoItem" data-id="' + item.id + '">' +
                            '<i class="fas fa-trash"></i> Excluir' +
                        '</button>' +
                    '</div>' +
                '</div>' +
            '</div>'
        );
        container.append(card);
        card.fadeIn(300);
    });
}

function initDescricaoTab() {

    $(document).off('click', '#btnAddDescricaoItem').on('click', '#btnAddDescricaoItem', function () {
        const rid = getRelatorioIdAtual();
        if (!rid) {
            showNotification('fas fa-exclamation-triangle', 'Salve o relatório antes de adicionar itens.', 'warning', 3500);
            return;
        }
        const texto = $('#descricao_item_texto').val().trim();
        if (!texto) {
            showNotification('fas fa-exclamation-triangle', 'Descreva o item antes de adicionar.', 'warning', 3000);
            return;
        }

        const fd = new FormData();
        fd.append('texto', texto);
        const fotoInput = document.getElementById('descricao_item_foto');
        if (fotoInput && fotoInput.files.length) {
            fd.append('foto', fotoInput.files[0]);
        }

        const btn = $(this);
        btn.prop('disabled', true);
        $.ajax({
            url: baseURL + '/atendimentos-relatorios/' + rid + '/descricao-itens',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (r) {
                $('#descricao_item_texto').val('');
                if (fotoInput) fotoInput.value = '';
                $('#descricao_item_foto').closest('.file-upload-group').find('.file-upload-text').text('Nenhuma foto selecionada');
                carregarDescricaoItens(rid);
                showNotification('fas fa-check-double', r.message, 'success', 2000);
                btn.prop('disabled', false);
            },
            error: function (xhr) { btn.prop('disabled', false); handleAjaxError(xhr); }
        });
    });

    $(document).off('click', '.btnRemoveDescricaoItem').on('click', '.btnRemoveDescricaoItem', function () {
        const rid = getRelatorioIdAtual();
        const itemId = $(this).data('id');
        $.ajax({
            url: baseURL + '/atendimentos-relatorios/' + rid + '/descricao-itens/' + itemId,
            type: 'DELETE',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (r) { carregarDescricaoItens(rid); showNotification('fas fa-check', r.message, 'success', 2000); },
            error: function (xhr) { handleAjaxError(xhr); }
        });
    });
}

// ─── Ocorrências ──────────────────────────────────────────────────────────────

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

