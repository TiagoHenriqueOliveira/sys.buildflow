{{-- NC01 — cadastro unificado de cliente. Virou tela dedicada (em vez de
     modal, como as demais telas simples) porque o volume de campos +
     contatos adicionais + geolocalização + histórico não cabem num modal
     sem virar uma rolagem infinita (ver docs/cronograma/02-web-nucleo-telas.md,
     que já previa essa alternativa). Um único <form> POST/PUT convencional
     (com redirect + $errors/old(), sem AJAX) — as abas (x-show) são só
     agrupamento visual, igual ao padrão das demais telas do sbadmin. A
     lista de contatos é gerenciada por Alpine (array `contatos`, semeado a
     partir do cliente/old() via Js::from() para sobreviver a um retorno de
     validação) e enviada junto no mesmo POST via inputs `contatos[i][campo]`. --}}
@php
    // Máscaras de exibição do valor inicial (old()/model) — espelham
    // window.formatarCnpj()/formatarTelefone() (resources/js/formatters.js),
    // que só reformatam a partir do evento oninput e por isso não cobrem o
    // valor já preenchido ao abrir a edição.
    $mascararCnpj = fn (?string $v) => $v && strlen($v) === 14
        ? preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $v)
        : $v;
    $mascararTelefone = fn (?string $v) => match (strlen((string) $v)) {
        11 => preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $v),
        10 => preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $v),
        default => $v,
    };

    $editando = $cliente->exists;
    $contatosIniciais = old('contatos', $editando
        ? $cliente->contatos->map(fn ($c) => [
            'nome' => $c->cli_cont_nome,
            'cargo' => $c->cli_cont_cargo,
            'telefone' => $mascararTelefone($c->cli_cont_telefone),
            'email' => $c->cli_cont_email,
            'tipo' => $c->cli_cont_tipo->value,
        ])->values()->all()
        : []);
    $equipamentosIniciais = old('equipamentos', $editando
        ? $cliente->equipamentos->map(fn ($e) => ['descricao' => $e->cli_equip_descricao])->values()->all()
        : []);
    $localizacoesIniciais = old('localizacoes', $editando
        ? $cliente->localizacoes->map(fn ($l) => [
            'descricao' => $l->cli_loc_descricao,
            'latitude' => $l->cli_loc_latitude,
            'longitude' => $l->cli_loc_longitude,
        ])->values()->all()
        : []);
    $isComercial = auth()->user()->user_nivel_acesso === \App\Enums\NivelAcesso::Comercial->value;
@endphp
<x-layout :title="$editando ? 'Clientes | Editar' : 'Clientes | Novo'">
    <div
        id="cliente-form-root"
        x-data="{
            tab: 'dados',
            contatos: {{ \Illuminate\Support\Js::from($contatosIniciais) }},
            equipamentos: {{ \Illuminate\Support\Js::from($equipamentosIniciais) }},
            localizacoes: {{ \Illuminate\Support\Js::from($localizacoesIniciais) }},
            lat: {{ \Illuminate\Support\Js::from(old('cli_latitude', $cliente->cli_latitude)) }},
            lng: {{ \Illuminate\Support\Js::from(old('cli_longitude', $cliente->cli_longitude)) }},
            addContato() { this.contatos.push({ nome: '', cargo: '', telefone: '', email: '', tipo: 0 }); },
            removerContato(i) { this.contatos.splice(i, 1); },
            addEquipamento() { this.equipamentos.push({ descricao: '' }); },
            removerEquipamento(i) { this.equipamentos.splice(i, 1); },
            addLocalizacao() { this.localizacoes.push({ descricao: '', latitude: '', longitude: '' }); },
            removerLocalizacao(i) { this.localizacoes.splice(i, 1); },
        }"
    >
        <div class="sbadmin-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="sbadmin-page-heading">{{ $editando ? 'Editar Cliente' : 'Novo Cliente' }}</h2>
                <p class="sbadmin-page-subheading">{{ $editando ? $cliente->cli_nome : 'Cadastre um novo cliente no sistema.' }}</p>
            </div>
            @if($editando)
                <x-sbadmin::badge :type="$cliente->cli_status->badgeType()">{{ $cliente->cli_status->label() }}</x-sbadmin::badge>
            @endif
        </div>

        <form
            id="form_cliente"
            method="POST"
            action="{{ $editando ? route('clientes.update', $cliente->cli_id) : route('clientes.store') }}"
        >
            @csrf
            @if($editando)
                @method('PUT')
            @endif

            <div class="sbadmin-card">
                <div class="sbadmin-card-body">
                    <ul class="nav nav-tabs mb-3 flex-nowrap overflow-x-auto overflow-y-hidden" role="tablist">
                        @foreach([
                            'dados' => 'Dados Gerais',
                            'contatos' => 'Contatos',
                            'geo' => 'Geolocalização',
                            'historico' => 'Histórico',
                        ] as $key => $label)
                            <li class="nav-item text-nowrap">
                                <button type="button" class="nav-link" :class="{ active: tab === '{{ $key }}' }" @click="tab = '{{ $key }}'">{{ $label }}</button>
                            </li>
                        @endforeach
                    </ul>

                    {{-- ── Dados Gerais ─────────────────────────────────────────── --}}
                    <div x-show="tab === 'dados'">
                        <x-sbadmin::form.input
                            id="cli_nome"
                            name="cli_nome"
                            label="Nome"
                            :value="old('cli_nome', $cliente->cli_nome)"
                            maxlength="100"
                            required
                            placeholder="Ex.: Empresa ABC Ltda"
                        />

                        <div class="row">
                            <div class="col-sm-3">
                                <x-sbadmin::form.input
                                    id="cli_cnpj"
                                    name="cli_cnpj"
                                    label="CNPJ"
                                    :value="old('cli_cnpj', $mascararCnpj($cliente->cli_cnpj))"
                                    maxlength="18"
                                    required
                                    placeholder="00.000.000/0000-00"
                                    oninput="this.value = window.formatarCnpj(this.value)"
                                />
                            </div>
                            <div class="col-sm-3">
                                <x-sbadmin::form.input
                                    id="cli_inscricao_estadual"
                                    name="cli_inscricao_estadual"
                                    label="Inscrição Estadual"
                                    :value="old('cli_inscricao_estadual', $cliente->cli_inscricao_estadual)"
                                    maxlength="20"
                                />
                            </div>
                            <div class="col-sm-4">
                                <x-sbadmin::form.input
                                    id="cli_cidade"
                                    name="cli_cidade"
                                    label="Cidade"
                                    :value="old('cli_cidade', $cliente->cli_cidade)"
                                    maxlength="100"
                                    required
                                />
                            </div>
                            <div class="col-sm-2">
                                <x-sbadmin::form.input
                                    id="cli_uf"
                                    name="cli_uf"
                                    label="UF"
                                    :value="old('cli_uf', $cliente->cli_uf)"
                                    maxlength="2"
                                    required
                                    placeholder="Ex.: SC"
                                    style="text-transform:uppercase"
                                />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-3">
                                <x-sbadmin::form.select
                                    id="cli_vendedor_id"
                                    name="cli_vendedor_id"
                                    label="Vendedor responsável"
                                    :options="$vendedores->pluck('user_nome', 'user_id')->all()"
                                    :value="old('cli_vendedor_id', $cliente->cli_vendedor_id)"
                                    placeholder="Nenhum"
                                />
                            </div>
                            <div class="col-sm-3">
                                <x-sbadmin::form.input
                                    id="cli_contato_principal"
                                    name="cli_contato_principal"
                                    label="Contato principal"
                                    :value="old('cli_contato_principal', $cliente->cli_contato_principal)"
                                    maxlength="100"
                                />
                            </div>
                            <div class="col-sm-3">
                                <x-sbadmin::form.input
                                    id="cli_telefone"
                                    name="cli_telefone"
                                    label="Telefone"
                                    :value="old('cli_telefone', $mascararTelefone($cliente->cli_telefone))"
                                    maxlength="15"
                                    placeholder="(00) 00000-0000"
                                    oninput="this.value = window.formatarTelefone(this.value)"
                                />
                            </div>
                            <div class="col-sm-3">
                                <x-sbadmin::form.input
                                    id="cli_email"
                                    type="email"
                                    name="cli_email"
                                    label="E-mail"
                                    :value="old('cli_email', $cliente->cli_email)"
                                    maxlength="100"
                                />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-4">
                                <x-sbadmin::form.input
                                    id="cli_segmento"
                                    name="cli_segmento"
                                    label="Segmento"
                                    :value="old('cli_segmento', $cliente->cli_segmento)"
                                    maxlength="255"
                                    placeholder="Ex.: Sucroenergético"
                                />
                            </div>
                            <div class="col-sm-4">
                                <x-sbadmin::form.select
                                    id="cli_classificacao_id"
                                    name="cli_classificacao_id"
                                    label="Classificação"
                                    :options="$classificacoes->pluck('cla_cli_nome', 'cla_cli_id')->all()"
                                    :value="old('cli_classificacao_id', $cliente->cli_classificacao_id)"
                                    placeholder="Nenhuma"
                                    :help="$classificacoes->isEmpty() ? 'Nenhuma classificação cadastrada ainda.' : null"
                                />
                            </div>
                            <div class="col-sm-4">
                                <x-sbadmin::form.input
                                    id="cli_dias_alerta_recontato"
                                    type="number"
                                    name="cli_dias_alerta_recontato"
                                    label="Alerta de recontato (dias)"
                                    :value="old('cli_dias_alerta_recontato', $cliente->cli_dias_alerta_recontato)"
                                    min="1"
                                    help="Dias sem contato até disparar alerta. Deixe em branco para não alertar."
                                />
                            </div>
                        </div>

                        @if($editando)
                            <div>
                                <input type="hidden" name="cli_ativo" value="0">
                                <x-sbadmin::form.checkbox
                                    id="cli_ativo"
                                    name="cli_ativo"
                                    label="Ativo"
                                    off-label="Inativo"
                                    :checked="old('cli_ativo', $cliente->cli_ativo)"
                                    :switch="true"
                                />
                            </div>
                        @endif
                    </div>

                    {{-- ── Contatos adicionais ──────────────────────────────────── --}}
                    <div x-show="tab === 'contatos'" x-cloak>
                        <template x-for="(contato, i) in contatos" :key="i">
                            <div class="sbadmin-card mb-3">
                                <div class="sbadmin-card-body">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="sbadmin-form-label">Nome</label>
                                            <input type="text" class="form-control sbadmin-form-control" maxlength="100" :name="'contatos['+i+'][nome]'" x-model="contato.nome">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="sbadmin-form-label">Cargo</label>
                                            <input type="text" class="form-control sbadmin-form-control" maxlength="100" :name="'contatos['+i+'][cargo]'" x-model="contato.cargo">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="sbadmin-form-label">Telefone</label>
                                            <input type="text" class="form-control sbadmin-form-control" maxlength="15" :name="'contatos['+i+'][telefone]'" x-model="contato.telefone" oninput="this.value = window.formatarTelefone(this.value)">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="sbadmin-form-label">E-mail</label>
                                            <input type="email" class="form-control sbadmin-form-control" maxlength="100" :name="'contatos['+i+'][email]'" x-model="contato.email">
                                        </div>
                                    </div>
                                    <div class="row g-2 mt-1 align-items-end">
                                        <div class="col-md-4">
                                            <label class="sbadmin-form-label">Tipo</label>
                                            <select class="form-select sbadmin-form-control" :name="'contatos['+i+'][tipo]'" x-model.number="contato.tipo">
                                                <option value="0">Técnico</option>
                                                <option value="1">Comercial</option>
                                            </select>
                                        </div>
                                        <div class="col-md-8 text-end">
                                            <button type="button" class="btn btn-outline-danger btn-sm" @click="removerContato(i)">
                                                <i class="bi bi-trash" aria-hidden="true"></i> Remover
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <p class="text-body-secondary small" x-show="contatos.length === 0">Nenhum contato adicional cadastrado.</p>

                        <button type="button" class="btn btn-outline-primary btn-sm" @click="addContato()">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i> Adicionar contato
                        </button>
                    </div>

                    {{-- ── Geolocalização (BF01) ────────────────────────────────── --}}
                    <div x-show="tab === 'geo'" x-cloak>
                        <h6 class="fw-bold">Localização principal</h6>
                        <div class="row">
                            <div class="col-sm-6">
                                <x-sbadmin::form.input id="cli_latitude" name="cli_latitude" label="Latitude" x-model.number="lat" readonly />
                            </div>
                            <div class="col-sm-6">
                                <x-sbadmin::form.input id="cli_longitude" name="cli_longitude" label="Longitude" x-model.number="lng" readonly />
                            </div>
                        </div>

                        @if($isComercial)
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="btnAtribuirLocalizacao">
                                    <i class="bi bi-geo-alt" aria-hidden="true"></i> Usar minha localização
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm" @click="abrirMapaPicker(null)">
                                    <i class="bi bi-map" aria-hidden="true"></i> Escolher no mapa
                                </button>
                                <a
                                    class="btn btn-outline-secondary btn-sm"
                                    :class="{ disabled: !lat || !lng }"
                                    :href="lat && lng ? ('https://www.google.com/maps?q=' + lat + ',' + lng) : '#'"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Abrir no Google Maps
                                </a>
                            </div>
                            <div id="geo-feedback" class="small text-body-secondary mt-2"></div>

                            <hr>

                            {{-- Pedido do cliente (2026-09-11): lista de localizações
                                 adicionais do mesmo cliente (empresa, instalação/
                                 montagem, manutenção...), cada uma com descrição
                                 livre + ponto próprio escolhido no mesmo picker do
                                 mapa. Não substitui lat/lng principal acima (ainda
                                 usada pelo mapa de relações — CRM07), é uma lista à
                                 parte, mesmo padrão de repetição da aba Contatos. --}}
                            <h6 class="fw-bold">Outras localizações</h6>
                            <template x-for="(localizacao, i) in localizacoes" :key="i">
                                <div class="sbadmin-card mb-3">
                                    <div class="sbadmin-card-body">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-md-5">
                                                <label class="sbadmin-form-label">Descrição</label>
                                                <input type="text" class="form-control sbadmin-form-control" maxlength="100" :name="'localizacoes['+i+'][descricao]'" x-model="localizacao.descricao" placeholder="Ex.: Empresa, Instalação/Montagem, Manutenção">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="sbadmin-form-label">Local</label>
                                                <input type="hidden" :name="'localizacoes['+i+'][latitude]'" x-model.number="localizacao.latitude">
                                                <input type="hidden" :name="'localizacoes['+i+'][longitude]'" x-model.number="localizacao.longitude">
                                                <div class="form-control sbadmin-form-control bg-transparent" x-text="localizacao.latitude && localizacao.longitude ? Number(localizacao.latitude).toFixed(5) + ', ' + Number(localizacao.longitude).toFixed(5) : 'Não definida'"></div>
                                            </div>
                                            <div class="col-md-3 d-flex gap-2">
                                                <button type="button" class="btn btn-outline-primary btn-sm" @click="abrirMapaPicker(i)">
                                                    <i class="bi bi-map" aria-hidden="true"></i> Escolher no mapa
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-sm" @click="removerLocalizacao(i)">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <p class="text-body-secondary small" x-show="localizacoes.length === 0">Nenhuma outra localização cadastrada.</p>

                            <button type="button" class="btn btn-outline-primary btn-sm" @click="addLocalizacao()">
                                <i class="bi bi-plus-lg" aria-hidden="true"></i> Adicionar localização
                            </button>

                            {{-- Picker de mapa (Leaflet/OpenStreetMap — sem chave de API),
                                 com busca por endereço via Nominatim (mesmo provedor,
                                 também sem chave). Reaproveitado tanto pela localização
                                 principal quanto por qualquer linha de "Outras
                                 localizações" — ver mapaPickerTargetIndex no script.
                                 Abre num modal próprio, separado do modal de edição
                                 padrão (não usa Bootstrap .modal pra evitar conflito de
                                 z-index/backdrop com o restante da tela). --}}
                            <div id="mapaPickerBackdrop" class="modal-backdrop show" style="display:none;"></div>
                            <div id="mapaPickerModal" class="modal" style="display:none;" tabindex="-1" role="dialog" aria-modal="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Escolher localização no mapa</h5>
                                            <button type="button" class="btn-close" aria-label="Fechar" id="btnFecharMapaPicker"></button>
                                        </div>
                                        <div class="modal-body p-0">
                                            <div class="d-flex gap-2 p-2 border-bottom">
                                                <input type="text" class="form-control sbadmin-form-control" id="mapaPickerBusca" placeholder="Buscar endereço ou local...">
                                                <button type="button" class="btn btn-outline-primary btn-sm text-nowrap" id="btnBuscarMapaPicker">
                                                    <i class="bi bi-search" aria-hidden="true"></i> Buscar
                                                </button>
                                            </div>
                                            <div id="mapaPickerLeaflet" style="height: 380px;"></div>
                                        </div>
                                        <div class="modal-footer">
                                            <span class="text-body-secondary small me-auto">Busque um endereço ou clique no mapa para posicionar o marcador.</span>
                                            <button type="button" class="btn btn-success" id="btnConfirmarMapaPicker">
                                                <i class="bi bi-check-lg" aria-hidden="true"></i> Usar esta localização
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" id="btnCancelarMapaPicker">
                                                <i class="bi bi-x-lg" aria-hidden="true"></i> Cancelar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <p class="text-body-secondary small">A captura de localização é feita por usuários com perfil Comercial.</p>
                        @endif
                    </div>

                    {{-- ── Histórico consolidado (placeholder — dados reais na Etapa 2) ── --}}
                    <div x-show="tab === 'historico'" x-cloak>
                        {{-- Pedido do cliente (2026-09-11): varios equipamentos, nao
                             mais um texto unico — mesmo padrao de repeticao da aba
                             Contatos. `cli_equipamento_vendido` (CRM07, popup do mapa
                             de relacoes) continua existindo, sincronizado como resumo
                             desta lista no Repository. --}}
                        <label class="sbadmin-form-label">Equipamentos</label>
                        <template x-for="(equipamento, i) in equipamentos" :key="i">
                            <div class="d-flex gap-2 mb-2">
                                <input type="text" class="form-control sbadmin-form-control" maxlength="255" :name="'equipamentos['+i+'][descricao]'" x-model="equipamento.descricao" placeholder="Ex.: ETE compacta 50m³/dia">
                                <button type="button" class="btn btn-outline-danger btn-sm" @click="removerEquipamento(i)">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>
                            </div>
                        </template>
                        <p class="text-body-secondary small" x-show="equipamentos.length === 0">Nenhum equipamento cadastrado.</p>
                        <button type="button" class="btn btn-outline-primary btn-sm mb-3" @click="addEquipamento()">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i> Adicionar equipamento
                        </button>

                        <x-sbadmin::form.checkbox
                            id="cli_caso_sucesso"
                            name="cli_caso_sucesso"
                            label="Caso de sucesso"
                            :checked="old('cli_caso_sucesso', $cliente->cli_caso_sucesso)"
                            :switch="true"
                        />

                        <x-sbadmin::form.textarea
                            id="cli_caso_sucesso_descricao"
                            name="cli_caso_sucesso_descricao"
                            label="Descrição do caso de sucesso"
                            :value="old('cli_caso_sucesso_descricao', $cliente->cli_caso_sucesso_descricao)"
                            rows="3"
                        />

                        <hr>

                        <p class="text-body-secondary">
                            @if($editando)
                                Histórico de atendimentos e orçamentos deste cliente — disponível a partir da sessão de persistência do Núcleo/CRM.
                            @else
                                Disponível após salvar o cadastro.
                            @endif
                        </p>
                    </div>
                </div>

                <div class="sbadmin-card-body d-flex justify-content-end gap-2 border-top">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg" aria-hidden="true"></i> Salvar
                    </button>
                    <a href="{{ route('clientes.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg" aria-hidden="true"></i> Cancelar
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Sem @stack('styles') disponivel no layout do sbadmin (só ha
         @stack('scripts')) -- o <link> do Leaflet fica direto aqui no
         corpo da pagina; funciona normalmente fora do <head>. --}}
    @if($isComercial)
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" integrity="sha512-h9FcoyWjHcOcmEVkxOfTLnmZFWIH0iZhZT1H2TbOq55xssQGEJHEaIm+PgoUaZbRvQTNTluNOEfb1ZRy6D3BOw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        @push('scripts')
            <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" integrity="sha512-puJW3E/qXDqYp9IfhAI54BJEaWIfloJ7JWs7OeD5i6ruC9JZL1gERT1wjtwXFlh7CjE7ZJ+/vcRZRkIYIb6p4g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
            <script>
                document.getElementById('btnAtribuirLocalizacao')?.addEventListener('click', function () {
                    const feedback = document.getElementById('geo-feedback');
                    if (!navigator.geolocation) {
                        feedback.textContent = 'Geolocalização não é suportada neste navegador.';
                        return;
                    }
                    feedback.textContent = 'Obtendo localização...';
                    navigator.geolocation.getCurrentPosition(
                        function (pos) {
                            const root = document.getElementById('cliente-form-root');
                            Alpine.$data(root).lat = Number(pos.coords.latitude.toFixed(7));
                            Alpine.$data(root).lng = Number(pos.coords.longitude.toFixed(7));
                            feedback.textContent = 'Localização atribuída com sucesso.';
                        },
                        function () {
                            feedback.textContent = 'Não foi possível obter a localização. Verifique a permissão do navegador.';
                        }
                    );
                });

                // ─── Escolher no mapa (Leaflet + OpenStreetMap, sem chave de API) ──
                // Reaproveitado pela localização principal (target = null) e por
                // qualquer linha de "Outras localizações" (target = índice da
                // linha) — window.abrirMapaPicker() é chamado direto dos botões
                // via Alpine (@click), por isso fica em window em vez de fechado
                // no escopo do <script> (Alpine avalia a expressão no escopo global).
                let mapaPicker = null;
                let marcadorPicker = null;
                let mapaPickerTargetIndex = null;

                window.abrirMapaPicker = function (targetIndex) {
                    mapaPickerTargetIndex = targetIndex;
                    const root = document.getElementById('cliente-form-root');
                    const alpine = Alpine.$data(root);
                    const alvo = targetIndex === null ? alpine : alpine.localizacoes[targetIndex];
                    const latInicial = (alvo && alvo.latitude) || alpine.lat || -25.4284;
                    const lngInicial = (alvo && alvo.longitude) || alpine.lng || -49.2733;
                    const temPonto = !!(alvo && alvo.latitude);

                    document.getElementById('mapaPickerBackdrop').style.display = 'block';
                    document.getElementById('mapaPickerModal').style.display = 'block';
                    document.getElementById('mapaPickerBusca').value = '';

                    setTimeout(function () {
                        if (!mapaPicker) {
                            mapaPicker = L.map('mapaPickerLeaflet').setView([latInicial, lngInicial], temPonto ? 15 : 5);
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                attribution: '&copy; OpenStreetMap',
                            }).addTo(mapaPicker);
                            marcadorPicker = L.marker([latInicial, lngInicial], { draggable: true }).addTo(mapaPicker);
                            mapaPicker.on('click', function (e) {
                                marcadorPicker.setLatLng(e.latlng);
                            });
                        } else {
                            mapaPicker.setView([latInicial, lngInicial], temPonto ? 15 : 5);
                            marcadorPicker.setLatLng([latInicial, lngInicial]);
                            mapaPicker.invalidateSize();
                        }
                    }, 50);
                };

                function fecharMapaPicker() {
                    document.getElementById('mapaPickerBackdrop').style.display = 'none';
                    document.getElementById('mapaPickerModal').style.display = 'none';
                }

                document.getElementById('btnCancelarMapaPicker')?.addEventListener('click', fecharMapaPicker);
                document.getElementById('btnFecharMapaPicker')?.addEventListener('click', fecharMapaPicker);
                document.getElementById('mapaPickerBackdrop')?.addEventListener('click', fecharMapaPicker);

                // Busca de endereco via Nominatim (OpenStreetMap) — mesmo provedor
                // do tileLayer acima, tambem sem chave de API. So centraliza o
                // mapa/marcador no resultado; a confirmacao continua manual
                // (botao "Usar esta localização"), pra o usuario poder ajustar o
                // pino antes de gravar.
                function buscarEnderecoMapaPicker() {
                    const termo = document.getElementById('mapaPickerBusca').value.trim();
                    if (!termo) return;

                    fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(termo))
                        .then((r) => r.json())
                        .then((resultados) => {
                            if (!resultados || !resultados.length) {
                                document.getElementById('geo-feedback').textContent = 'Nenhum resultado encontrado para o endereço buscado.';
                                return;
                            }
                            const lat = parseFloat(resultados[0].lat);
                            const lon = parseFloat(resultados[0].lon);
                            mapaPicker.setView([lat, lon], 16);
                            marcadorPicker.setLatLng([lat, lon]);
                        })
                        .catch(() => { document.getElementById('geo-feedback').textContent = 'Erro ao buscar o endereço.'; });
                }

                document.getElementById('btnBuscarMapaPicker')?.addEventListener('click', buscarEnderecoMapaPicker);
                document.getElementById('mapaPickerBusca')?.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        buscarEnderecoMapaPicker();
                    }
                });

                document.getElementById('btnConfirmarMapaPicker')?.addEventListener('click', function () {
                    const pos = marcadorPicker.getLatLng();
                    const root = document.getElementById('cliente-form-root');
                    const alpine = Alpine.$data(root);

                    if (mapaPickerTargetIndex === null) {
                        alpine.lat = Number(pos.lat.toFixed(7));
                        alpine.lng = Number(pos.lng.toFixed(7));
                        document.getElementById('geo-feedback').textContent = 'Localização atribuída com sucesso.';
                    } else {
                        alpine.localizacoes[mapaPickerTargetIndex].latitude = Number(pos.lat.toFixed(7));
                        alpine.localizacoes[mapaPickerTargetIndex].longitude = Number(pos.lng.toFixed(7));
                    }
                    fecharMapaPicker();
                });
            </script>
        @endpush
    @endif
</x-layout>