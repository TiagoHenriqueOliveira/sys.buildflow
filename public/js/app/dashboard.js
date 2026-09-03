/* ============================================================
 *  dashboard.js  –  BI Dashboard do Sys.Buildflow
 * ============================================================ */

(function () {
    'use strict';

    // ── Instâncias dos gráficos ────────────────────────────────────────────
    var charts = {};
    var selectedUf = null;   // UF selecionada no mapa (ex: "SP")

    // ── Mapeamento UF ↔ nome completo (topology usa nomes completos) ───────
    var UF_TO_NAME = {
        AC: 'Acre',                AP: 'Amapá',             AM: 'Amazonas',
        BA: 'Bahia',               CE: 'Ceará',             DF: 'Distrito Federal',
        ES: 'Espírito Santo',      GO: 'Goiás',             MA: 'Maranhão',
        MT: 'Mato Grosso',         MS: 'Mato Grosso do Sul',MG: 'Minas Gerais',
        PA: 'Pará',                PB: 'Paraíba',           PR: 'Paraná',
        PE: 'Pernambuco',          PI: 'Piauí',             RJ: 'Rio de Janeiro',
        RN: 'Rio Grande do Norte', RS: 'Rio Grande do Sul', RO: 'Rondônia',
        RR: 'Roraima',             SC: 'Santa Catarina',    SP: 'São Paulo',
        SE: 'Sergipe',             TO: 'Tocantins',         AL: 'Alagoas'
    };

    var NAME_TO_UF = {};
    Object.keys(UF_TO_NAME).forEach(function (k) { NAME_TO_UF[UF_TO_NAME[k]] = k; });

    // ── Cache do topology ──────────────────────────────────────────────────
    var topoCache = null;
    var byNameCache = {};   // dados actuais: nome → total

    // ── Utilitários ────────────────────────────────────────────────────────
    function apiGet(path, params, callback) {
        var url = baseURL + path;
        if (params && Object.keys(params).length) {
            url += '?' + $.param(params);
        }
        $.getJSON(url, callback).fail(function () {
            console.error('Erro ao buscar ' + path);
        });
    }

    function destroyChart(id) {
        if (charts[id]) { charts[id].destroy(); delete charts[id]; }
    }

    // ── 0. KPIs ────────────────────────────────────────────────────────────
    function loadKpis() {
        apiGet('/dashboard/data/kpis', {}, function (d) {
            $('#kpiTotal').text(d.total);
            $('#kpiEmAndamento').text(d.em_andamento);
            $('#kpiConcluidos').text(d.concluidos);
            $('#kpiRelatorios').text(d.relatorios);
        });
    }

    // ── 1. Donut: por Status ───────────────────────────────────────────────
    function loadStatus() {
        var params = {
            dt_inicio: $('#statusDtInicio').val(),
            dt_fim:    $('#statusDtFim').val(),
        };
        apiGet('/dashboard/data/por-status', params, function (d) {
            destroyChart('status');
            var ctx = document.getElementById('chartStatus').getContext('2d');
            charts['status'] = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: d.labels,
                    datasets: [{
                        data: d.data,
                        backgroundColor: d.colors,
                        hoverBorderColor: 'rgba(234, 236, 244, 1)',
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    legend: { display: true, position: 'bottom' },
                    cutoutPercentage: 60,
                    tooltips: {
                        callbacks: {
                            label: function (item, data) {
                                var total = data.datasets[0].data.reduce(function (a, b) { return a + b; }, 0);
                                var val   = data.datasets[0].data[item.index];
                                var pct   = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                return ' ' + data.labels[item.index] + ': ' + val + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            });
        });
    }

    // ── 2. Line: Evolução de Relatórios ───────────────────────────────────
    function loadEvolucao(meses) {
        apiGet('/dashboard/data/evolucao', { meses: meses }, function (d) {
            destroyChart('evolucao');
            var ctx = document.getElementById('chartEvolucao').getContext('2d');
            charts['evolucao'] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: d.labels,
                    datasets: [{
                        label: 'Relatórios criados',
                        data: d.data,
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78,115,223,0.08)',
                        pointBackgroundColor: '#4e73df',
                        pointRadius: 4,
                        fill: true,
                        tension: 0.35,
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        xAxes: [{ gridLines: { display: false }, ticks: { maxRotation: 45 } }],
                        yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }]
                    },
                    legend: { display: false },
                    tooltips: { mode: 'index', intersect: false }
                }
            });
        });
    }

    // ── 3. Bar: Relatórios por Cliente — Em Aberto (empilhado) ───────────────
    var BAR_HEIGHT = 46;   // px por barra
    var BAR_MARGIN = 90;   // margem (inclui legenda no topo)

    function loadMaisRelatorios() {
        var limite = $('#maisRelatoriosLimite').val();

        apiGet('/dashboard/data/mais-relatorios', { limite: limite }, function (d) {
            destroyChart('maisRelatorios');

            // Altura dinâmica: mínimo 280px, escala com o número de itens
            var h = Math.max(280, (d.labels.length * BAR_HEIGHT) + BAR_MARGIN);
            var wrapper = document.getElementById('chartMaisRelatoriosWrapper');
            wrapper.style.height = h + 'px';

            var ctx = document.getElementById('chartMaisRelatorios').getContext('2d');
            charts['maisRelatorios'] = new Chart(ctx, {
                type: 'horizontalBar',
                data: {
                    labels: d.labels,
                    datasets: d.datasets.map(function (ds) {
                        return {
                            label: ds.label,
                            data: ds.data,
                            backgroundColor: ds.color,
                            barThickness: 24,
                        };
                    })
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        xAxes: [{ stacked: true, ticks: { beginAtZero: true, precision: 0 } }],
                        yAxes: [{ stacked: true, ticks: { fontSize: 12 } }]
                    },
                    legend: { display: true, position: 'top' },
                    tooltips: {
                        mode: 'index',
                        intersect: false,
                    }
                }
            });
        });
    }

    // ── 4. Mapa D3 do Brasil — estilo Azul Profissional ───────────────────

    // Paleta: cinza suave → azul índigo
    var MAP_STYLE = {
        bg:          '#f1f5f9',
        empty:       '#e2e8f0',
        colorLow:    '#bfdbfe',
        colorHigh:   '#1e3a8a',
        stroke:      '#cbd5e1',
        strokeW:     0.8,
        strokeSel:   '#1e3a8a',
        strokeSelW:  2.5,
        strokeHover: '#4e73df',
        strokeHoverW:1.8,
        labelFill:   '#475569',
        labelSize:   '9px',
    };

    var TOPO_URL = 'https://cdn.jsdelivr.net/npm/datamaps@0.5.10/src/js/data/bra.topo.json';

    function buildColorScale(byName) {
        var max = d3.max(Object.values(byName)) || 1;
        return function (name) {
            var v = byName[name] || 0;
            if (!v) return MAP_STYLE.empty;
            return d3.interpolate(MAP_STYLE.colorLow, MAP_STYLE.colorHigh)(v / max);
        };
    }

    function renderMapD3(data) {
        // Converter UF → nome completo
        byNameCache = {};
        data.forEach(function (row) {
            var name = UF_TO_NAME[row.uf.toUpperCase()];
            if (name) byNameCache[name] = row.total;
        });

        var container = document.getElementById('brazilMapContainer');
        var W = container.clientWidth || 420;
        var H = Math.round(W * 1.15);   // proporção do Brasil

        // Remover SVG anterior se houver
        d3.select('#brazilMapContainer svg').remove();

        var svg = d3.select('#brazilMapContainer')
            .append('svg')
            .attr('viewBox', '0 0 ' + W + ' ' + H)
            .attr('width', '100%')
            .style('display', 'block')
            .style('border-radius', '8px');

        // Fundo
        svg.append('rect')
            .attr('width', W).attr('height', H)
            .attr('fill', MAP_STYLE.bg)
            .attr('rx', 8);

        var proj = d3.geoMercator()
            .center([-54, -15])
            .scale(W * 1.28)
            .translate([W / 2, H / 2 + H * 0.04]);

        var path = d3.geoPath().projection(proj);
        var colorScale = buildColorScale(byNameCache);
        var tooltip = document.getElementById('mapTooltip');

        function loadAndRender(topo) {
            var features = topojson.feature(topo, topo.objects.bra).features;

            svg.selectAll('path').remove();

            svg.selectAll('path')
                .data(features)
                .join('path')
                .attr('d', path)
                .attr('fill', function (d) { return colorScale(d.properties.name); })
                .attr('stroke', function (d) {
                    var uf = NAME_TO_UF[d.properties.name];
                    return uf === selectedUf ? MAP_STYLE.strokeSel : MAP_STYLE.stroke;
                })
                .attr('stroke-width', function (d) {
                    var uf = NAME_TO_UF[d.properties.name];
                    return uf === selectedUf ? MAP_STYLE.strokeSelW : MAP_STYLE.strokeW;
                })
                .style('cursor', 'pointer')
                .on('mouseenter', function (event, d) {
                    var uf    = NAME_TO_UF[d.properties.name] || '—';
                    var count = byNameCache[d.properties.name] || 0;
                    tooltip.textContent = d.properties.name + ' (' + uf + '): ' + count + ' atendimento' + (count !== 1 ? 's' : '');
                    tooltip.style.display = 'block';

                    if (NAME_TO_UF[d.properties.name] !== selectedUf) {
                        d3.select(this)
                            .attr('stroke', MAP_STYLE.strokeHover)
                            .attr('stroke-width', MAP_STYLE.strokeHoverW);
                    }
                })
                .on('mousemove', function (event) {
                    tooltip.style.top  = (event.clientY - 38) + 'px';
                    tooltip.style.left = (event.clientX + 14) + 'px';
                })
                .on('mouseleave', function (event, d) {
                    tooltip.style.display = 'none';
                    var uf = NAME_TO_UF[d.properties.name];
                    d3.select(this)
                        .attr('stroke', uf === selectedUf ? MAP_STYLE.strokeSel : MAP_STYLE.stroke)
                        .attr('stroke-width', uf === selectedUf ? MAP_STYLE.strokeSelW : MAP_STYLE.strokeW);
                })
                .on('click', function (event, d) {
                    var uf = NAME_TO_UF[d.properties.name];
                    if (!uf) return;

                    if (selectedUf === uf) {
                        selectedUf = null;
                        $('#tecnicoEstadoLabel').text('');
                    } else {
                        selectedUf = uf;
                        $('#tecnicoEstadoLabel').text('— ' + d.properties.name);
                    }

                    // Atualizar destaque sem re-fetch
                    svg.selectAll('path')
                        .attr('stroke', function (feat) {
                            return NAME_TO_UF[feat.properties.name] === selectedUf ? MAP_STYLE.strokeSel : MAP_STYLE.stroke;
                        })
                        .attr('stroke-width', function (feat) {
                            return NAME_TO_UF[feat.properties.name] === selectedUf ? MAP_STYLE.strokeSelW : MAP_STYLE.strokeW;
                        });

                    loadTecnico(selectedUf);
                });

            // Legenda de cor
            renderMapLegend(svg, W, H);
        }

        if (topoCache) {
            loadAndRender(topoCache);
        } else {
            d3.json(TOPO_URL).then(function (topo) {
                topoCache = topo;
                loadAndRender(topo);
            });
        }
    }

    function renderMapLegend(svg, W, H) {
        var legW = 100, legH = 10, legX = W - legW - 12, legY = H - 28;
        var defs = svg.append('defs');
        var grad = defs.append('linearGradient').attr('id', 'mapGrad');
        grad.append('stop').attr('offset', '0%').attr('stop-color', MAP_STYLE.colorLow);
        grad.append('stop').attr('offset', '100%').attr('stop-color', MAP_STYLE.colorHigh);

        svg.append('rect')
            .attr('x', legX).attr('y', legY)
            .attr('width', legW).attr('height', legH)
            .attr('rx', 3)
            .attr('fill', 'url(#mapGrad)')
            .attr('stroke', MAP_STYLE.stroke)
            .attr('stroke-width', 0.5);

        svg.append('text').attr('x', legX).attr('y', legY - 3)
            .attr('font-size', MAP_STYLE.labelSize).attr('fill', MAP_STYLE.labelFill)
            .text('Menos');
        svg.append('text').attr('x', legX + legW).attr('y', legY - 3)
            .attr('text-anchor', 'end')
            .attr('font-size', MAP_STYLE.labelSize).attr('fill', MAP_STYLE.labelFill)
            .text('Mais');
    }

    function loadEstado() {
        var tecnicoId = isAdmin ? ($('#mapaTecnicoFiltro').val() || '') : '';
        apiGet('/dashboard/data/por-estado', { tecnico_id: tecnicoId }, function (resp) {
            renderMapD3(resp.data);
        });
    }

    // ── 5. Bar: por Técnico ────────────────────────────────────────────────
    function loadTecnico(uf) {
        if (!isAdmin) return;
        apiGet('/dashboard/data/por-tecnico', { uf: uf || '' }, function (d) {
            destroyChart('tecnico');
            if (!d.labels || d.labels.length === 0) {
                $('#chartTecnico').hide();
                return;
            }
            $('#chartTecnico').show();
            var ctx = document.getElementById('chartTecnico').getContext('2d');
            charts['tecnico'] = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: d.labels,
                    datasets: [{
                        label: 'Atendimentos',
                        data: d.data,
                        backgroundColor: '#4e73df',
                        hoverBackgroundColor: '#2e59d9',
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        xAxes: [{ ticks: { fontSize: 11 } }],
                        yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }]
                    },
                    legend: { display: false },
                }
            });
        });
    }

    // ── 6. Pie: por Setor ─────────────────────────────────────────────────
    function loadSetor() {
        apiGet('/dashboard/data/por-setor', {}, function (d) {
            destroyChart('setor');
            var ctx = document.getElementById('chartSetor').getContext('2d');
            charts['setor'] = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: d.labels,
                    datasets: [{
                        data: d.data,
                        backgroundColor: d.colors,
                        hoverBorderColor: 'rgba(234,236,244,1)',
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    legend: { display: true, position: 'bottom', labels: { fontSize: 11 } },
                    tooltips: {
                        callbacks: {
                            label: function (item, data) {
                                var total = data.datasets[0].data.reduce(function (a, b) { return a + b; }, 0);
                                var val   = data.datasets[0].data[item.index];
                                var pct   = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                return ' ' + data.labels[item.index] + ': ' + val + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            });
        });
    }

    // ── 7. Bar: Tempo Médio ────────────────────────────────────────────────
    function loadTempoMedio() {
        apiGet('/dashboard/data/tempo-medio', {}, function (d) {
            destroyChart('tempoMedio');
            var ctx = document.getElementById('chartTempoMedio').getContext('2d');
            charts['tempoMedio'] = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: d.labels,
                    datasets: [{
                        label: 'Média (dias)',
                        data: d.data,
                        backgroundColor: d.colors,
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        xAxes: [{ gridLines: { display: false } }],
                        yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }]
                    },
                    legend: { display: false },
                    tooltips: {
                        callbacks: {
                            label: function (item) {
                                return ' ' + item.yLabel + ' dias em média';
                            }
                        }
                    }
                }
            });
        });
    }

    // ── Inicialização ──────────────────────────────────────────────────────
    $(function () {
        Chart.defaults.global.defaultFontFamily = '"Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
        Chart.defaults.global.defaultFontColor  = '#858796';

        loadKpis();
        loadStatus();
        loadEvolucao(12);
        loadMaisRelatorios();
        loadEstado();
        loadTecnico(null);
        loadSetor();
        loadTempoMedio();

        // ── Event listeners ────────────────────────────────────────────────

        $('#evolucaoMeses').on('change', function () {
            loadEvolucao($(this).val());
        });

        $('#statusDtInicio, #statusDtFim').on('change', function () {
            loadStatus();
        });

        $('#maisRelatoriosLimite').on('change', function () {
            loadMaisRelatorios();
        });

        $('#mapaTecnicoFiltro').on('change', function () {
            selectedUf = null;
            $('#tecnicoEstadoLabel').text('');
            loadEstado();
            loadTecnico(null);
        });

        // Re-renderizar mapa se janela for redimensionada
        var resizeTimer;
        $(window).on('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                if (Object.keys(byNameCache).length) {
                    renderMapD3(
                        Object.keys(byNameCache).map(function (name) {
                            var uf = NAME_TO_UF[name] || '';
                            return { uf: uf, total: byNameCache[name] };
                        })
                    );
                }
            }, 250);
        });
    });

})();
