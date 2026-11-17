{{-- BF08 - mapa de demandas por atendimento. Mesmo padrao Leaflet/
     OpenStreetMap ja validado em mapa-relacoes/index.blade.php (CRM07) -
     mesma versao/hash SRI, mesma funcao escapeHtml() (campos como cliente/
     natureza sao texto livre editavel por usuarios administrativos, ver
     nota de seguranca no commit de mapa-relacoes). --}}
<x-layout title="Mapa de Demandas">
    <div class="sbadmin-page-header">
        <h2 class="sbadmin-page-heading">Mapa de Demandas</h2>
        <p class="sbadmin-page-subheading">Atendimentos no mapa, coloridos por status.</p>
    </div>

    <div class="sbadmin-card mb-3">
        <div class="sbadmin-card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-sm-4">
                    <label class="sbadmin-form-label" for="f_status">Status</label>
                    <select class="form-select sbadmin-form-control" id="f_status" name="f_status">
                        <option value="">Todos</option>
                        @foreach($statusList as $s)
                            <option value="{{ $s->value }}" @selected($filtroStatus == $s->value)>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-4">
                    <label class="sbadmin-form-label" for="f_natureza">Natureza</label>
                    <select class="form-select sbadmin-form-control" id="f_natureza" name="f_natureza">
                        <option value="">Todas</option>
                        @foreach($naturezas as $n)
                            <option value="{{ $n->nat_aten_id }}" @selected($filtroNatureza == $n->nat_aten_id)>{{ $n->nat_aten_descricao }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-4">
                    <label class="sbadmin-form-label" for="f_tecnico">Técnico</label>
                    <select class="form-select sbadmin-form-control" id="f_tecnico" name="f_tecnico">
                        <option value="">Todos</option>
                        @foreach($tecnicos as $t)
                            <option value="{{ $t->user_id }}" @selected($filtroTecnico == $t->user_id)>{{ $t->user_nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-info text-white">
                        <i class="bi bi-funnel" aria-hidden="true"></i> Aplicar
                    </button>
                    <a href="{{ route('mapa-demandas.index') }}" class="btn btn-outline-secondary">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex gap-3 flex-wrap mb-3">
        @foreach($statusList as $s)
            <span class="d-inline-flex align-items-center gap-1 small">
                <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:{{ ['#6c757d', '#ffc107', '#0d6efd', '#198754'][$s->value] }}"></span>
                {{ $s->label() }}
            </span>
        @endforeach
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" integrity="sha512-h9FcoyWjHcOcmEVkxOfTLnmZFWIH0iZhZT1H2TbOq55xssQGEJHEaIm+PgoUaZbRvQTNTluNOEfb1ZRy6D3BOw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <div class="sbadmin-card">
        <div class="sbadmin-card-body">
            @if($pontos === [])
                <p class="text-body-secondary mb-0">Nenhum atendimento com cliente geolocalizado para os filtros selecionados.</p>
            @else
                <div id="mapaDemandas" style="height: 600px;"></div>
            @endif
        </div>
    </div>

    {{-- Pedido do cliente (2026-11-17): um marcador por LOCAL, não por
         atendimento — atendimentos do mesmo cliente caíam na mesma
         coordenada e a bolinha de cima escondia a de baixo. O número dentro
         do marcador indica quantos atendimentos há ali; a cor é a do mais
         pendente; o popup lista todos (ver App\Support\PontosMapaDemandas). --}}
    <style>
        .mapa-ponto {
            display: flex; align-items: center; justify-content: center;
            width: 100%; height: 100%; border-radius: 50%;
            border: 2px solid #fff; box-shadow: 0 0 3px rgba(0, 0, 0, .5);
            color: #fff; font-size: 12px; font-weight: 700; line-height: 1;
        }
        .mapa-ponto-popup { max-height: 280px; overflow-y: auto; min-width: 220px; }
        .mapa-ponto-popup .item + .item { border-top: 1px solid #dee2e6; margin-top: 6px; padding-top: 6px; }
        .mapa-ponto-popup .bolinha { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 4px; }
    </style>

    @if($pontos !== [])
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" integrity="sha512-puJW3E/qXDqYp9IfhAI54BJEaWIfloJ7JWs7OeD5i6ruC9JZL1gERT1wjtwXFlh7CjE7ZJ+/vcRZRkIYIb6p4g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var cores = ['#6c757d', '#ffc107', '#0d6efd', '#198754'];
                var pontos = {{ \Illuminate\Support\Js::from($pontos) }};
                var urlAtendimento = @json($podeAbrirAtendimento ? route('atendimentos.edit', ['id' => '__ID__']) : null);

                function escapeHtml(valor) {
                    return String(valor)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                }

                var mapa = L.map('mapaDemandas');
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    maxZoom: 19,
                }).addTo(mapa);

                var bounds = [];
                pontos.forEach(function (p) {
                    var popup = '<div class="mapa-ponto-popup">'
                        + '<strong>' + escapeHtml(p.clientes.join(' / ') || 'Sem cliente') + '</strong>';
                    if (p.total > 1) { popup += '<br><small>' + p.total + ' atendimentos neste local</small>'; }

                    p.atendimentos.forEach(function (a) {
                        popup += '<div class="item">'
                            + '<span class="bolinha" style="background:' + (cores[a.status] || '#6c757d') + '"></span>'
                            + '<strong>' + escapeHtml(a.status_label) + '</strong>';
                        if (p.clientes.length > 1) { popup += '<br>Cliente: ' + escapeHtml(a.cliente); }
                        if (a.tecnico) { popup += '<br>Técnico: ' + escapeHtml(a.tecnico); }
                        if (a.natureza) { popup += '<br>Natureza: ' + escapeHtml(a.natureza); }
                        if (a.proposta) { popup += '<br>Proposta/OSV: ' + escapeHtml(a.proposta); }
                        if (urlAtendimento) {
                            popup += '<br><a href="' + urlAtendimento.replace('__ID__', encodeURIComponent(a.id)) + '">Abrir atendimento</a>';
                        }
                        popup += '</div>';
                    });
                    popup += '</div>';

                    var tamanho = p.total > 1 ? 26 : 18;
                    var icone = L.divIcon({
                        className: '',
                        html: '<span class="mapa-ponto" style="background:' + (cores[p.status] || '#6c757d') + '">'
                            + (p.total > 1 ? p.total : '') + '</span>',
                        iconSize: [tamanho, tamanho],
                        iconAnchor: [tamanho / 2, tamanho / 2],
                        popupAnchor: [0, -tamanho / 2],
                    });

                    L.marker([p.latitude, p.longitude], {
                        icon: icone,
                        title: p.clientes.join(' / ') + (p.total > 1 ? ' (' + p.total + ' atendimentos)' : ''),
                    }).addTo(mapa).bindPopup(popup);

                    bounds.push([p.latitude, p.longitude]);
                });

                if (bounds.length === 1) {
                    mapa.setView(bounds[0], 13);
                } else {
                    mapa.fitBounds(bounds, { padding: [30, 30] });
                }
            });
        </script>
    @endif
</x-layout>