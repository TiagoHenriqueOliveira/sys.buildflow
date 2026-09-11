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
            @if($atendimentos->isEmpty())
                <p class="text-body-secondary mb-0">Nenhum atendimento com cliente geolocalizado para os filtros selecionados.</p>
            @else
                <div id="mapaDemandas" style="height: 600px;"></div>
            @endif
        </div>
    </div>

    @if($atendimentos->isNotEmpty())
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" integrity="sha512-puJW3E/qXDqYp9IfhAI54BJEaWIfloJ7JWs7OeD5i6ruC9JZL1gERT1wjtwXFlh7CjE7ZJ+/vcRZRkIYIb6p4g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var cores = ['#6c757d', '#ffc107', '#0d6efd', '#198754'];
                var atendimentos = {!! $atendimentos->map(fn ($a) => [
                    'cliente' => optional($a->cliente)->cli_nome,
                    'lat' => optional($a->cliente)->cli_latitude,
                    'lng' => optional($a->cliente)->cli_longitude,
                    'natureza' => optional($a->natureza)->nat_aten_descricao,
                    'tecnico' => optional($a->usuario)->user_nome,
                    'proposta' => $a->aten_nr_proposta,
                    'status' => $a->aten_status,
                    'statusLabel' => \App\Enums\AtendimentoStatus::from($a->aten_status)->label(),
                ])->values()->toJson() !!};

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
                atendimentos.forEach(function (a) {
                    var popup = '<strong>' + escapeHtml(a.cliente || 'Sem cliente') + '</strong>';
                    if (a.proposta) { popup += '<br>Proposta/OSV: ' + escapeHtml(a.proposta); }
                    if (a.natureza) { popup += '<br>Natureza: ' + escapeHtml(a.natureza); }
                    if (a.tecnico) { popup += '<br>Técnico: ' + escapeHtml(a.tecnico); }
                    popup += '<br>Status: ' + escapeHtml(a.statusLabel);

                    var marker = L.circleMarker([a.lat, a.lng], {
                        radius: 9,
                        color: '#fff',
                        weight: 2,
                        fillColor: cores[a.status] || '#6c757d',
                        fillOpacity: 0.9,
                    }).addTo(mapa).bindPopup(popup);

                    bounds.push([a.lat, a.lng]);
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