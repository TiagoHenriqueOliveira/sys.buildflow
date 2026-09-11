{{-- CRM07 — mapa de relações de clientes. Reaproveita o mesmo Leaflet +
     OpenStreetMap já usado no picker de geolocalização de clientes/form.blade.php
     (mesmas versões/hashes SRI, já validadas ali). Filtros recarregam a página
     (mesmo padrão simples usado em orcamentos/roteiros-viagem — sem AJAX). --}}
<x-layout title="Mapa de Relações de Clientes">
    <div class="sbadmin-page-header">
        <h2 class="sbadmin-page-heading">Mapa de Relações de Clientes</h2>
        <p class="sbadmin-page-subheading">Visualize os clientes no mapa, com equipamento vendido e casos de sucesso.</p>
    </div>

    <div class="sbadmin-card mb-3">
        <div class="sbadmin-card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-sm-6 col-md-3">
                    <label class="sbadmin-form-label" for="f_vendedor">Vendedor</label>
                    <select class="form-select sbadmin-form-control" id="f_vendedor" name="f_vendedor">
                        <option value="">Todos</option>
                        @foreach($vendedores as $v)
                            <option value="{{ $v->user_id }}" @selected($filtroVendedor == $v->user_id)>{{ $v->user_nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="sbadmin-form-label" for="f_estado">Estado (UF)</label>
                    <select class="form-select sbadmin-form-control" id="f_estado" name="f_estado">
                        <option value="">Todos</option>
                        @foreach($estados as $uf)
                            <option value="{{ $uf }}" @selected($filtroEstado === $uf)>{{ $uf }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="sbadmin-form-label" for="f_segmento">Segmento</label>
                    <select class="form-select sbadmin-form-control" id="f_segmento" name="f_segmento">
                        <option value="">Todos</option>
                        @foreach($segmentos as $seg)
                            <option value="{{ $seg }}" @selected($filtroSegmento === $seg)>{{ $seg }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="sbadmin-form-label" for="f_classificacao">Classificação</label>
                    <select class="form-select sbadmin-form-control" id="f_classificacao" name="f_classificacao">
                        <option value="">Todas</option>
                        @foreach($classificacoes as $c)
                            <option value="{{ $c->cla_cli_id }}" @selected($filtroClassificacao == $c->cla_cli_id)>{{ $c->cla_cli_nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-info text-white">
                        <i class="bi bi-funnel" aria-hidden="true"></i> Aplicar
                    </button>
                    <a href="{{ route('mapa-relacoes.index') }}" class="btn btn-outline-secondary">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" integrity="sha512-h9FcoyWjHcOcmEVkxOfTLnmZFWIH0iZhZT1H2TbOq55xssQGEJHEaIm+PgoUaZbRvQTNTluNOEfb1ZRy6D3BOw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <div class="sbadmin-card">
        <div class="sbadmin-card-body">
            @if($clientes->isEmpty())
                <p class="text-body-secondary mb-0">Nenhum cliente com geolocalização cadastrada para os filtros selecionados.</p>
            @else
                <div id="mapaRelacoes" style="height: 600px;"></div>
            @endif
        </div>
    </div>

    @if($clientes->isNotEmpty())
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" integrity="sha512-puJW3E/qXDqYp9IfhAI54BJEaWIfloJ7JWs7OeD5i6ruC9JZL1gERT1wjtwXFlh7CjE7ZJ+/vcRZRkIYIb6p4g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var clientes = {!! $clientes->map(fn ($c) => [
                    'nome' => $c->cli_nome,
                    'lat' => $c->cli_latitude,
                    'lng' => $c->cli_longitude,
                    'vendedor' => optional($c->vendedor)->user_nome,
                    'classificacao' => optional($c->classificacao)->cla_cli_nome,
                    'segmento' => $c->cli_segmento,
                    'equipamento' => $c->cli_equipamento_vendido,
                    'casoSucesso' => $c->cli_caso_sucesso,
                    'casoSucessoDescricao' => $c->cli_caso_sucesso_descricao,
                ])->values()->toJson() !!};

                var mapa = L.map('mapaRelacoes');
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    maxZoom: 19,
                }).addTo(mapa);

                var bounds = [];
                // Campos de cliente (nome, segmento, equipamento, descricao do
                // caso de sucesso) sao texto livre editavel por qualquer usuario
                // Comercial/Admin — escapar antes de concatenar no HTML do popup
                // evita XSS armazenado de um usuario para outro via esses campos.
                function escapeHtml(valor) {
                    return String(valor)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                }

                clientes.forEach(function (c) {
                    var popup = '<strong>' + escapeHtml(c.nome) + '</strong>';
                    if (c.vendedor) { popup += '<br>Vendedor: ' + escapeHtml(c.vendedor); }
                    if (c.classificacao) { popup += '<br>Classificação: ' + escapeHtml(c.classificacao); }
                    if (c.segmento) { popup += '<br>Segmento: ' + escapeHtml(c.segmento); }
                    if (c.equipamento) { popup += '<br>Equipamento vendido: ' + escapeHtml(c.equipamento); }
                    if (c.casoSucesso) {
                        popup += '<br><span class="badge bg-success">Caso de sucesso</span>';
                        if (c.casoSucessoDescricao) { popup += '<br>' + escapeHtml(c.casoSucessoDescricao); }
                    }

                    L.marker([c.lat, c.lng]).addTo(mapa).bindPopup(popup);
                    bounds.push([c.lat, c.lng]);
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