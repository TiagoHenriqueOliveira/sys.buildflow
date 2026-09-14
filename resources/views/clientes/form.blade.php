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
            'link_mapa' => $l->cli_loc_link_mapa,
        ])->values()->all()
        : []);
    // Geolocalizacao: Administrador, Comercial e Assistencia podem capturar
    // localizacao; Tecnico nao (pedido do cliente, 2026-09-11). A rota de
    // Clientes em si continua restrita a Administrador/Comercial (ver
    // routes/web.php, middleware "comercial") ate a definicao futura das
    // regras de acesso de Assistencia/Vendedor — este flag so controla o
    // que aparece DENTRO da aba, ficando pronto pra quando isso mudar.
    $nivelAtual = auth()->user()->user_nivel_acesso;
    $podeGeolocalizar = in_array($nivelAtual, [
        \App\Enums\NivelAcesso::Administrador->value,
        \App\Enums\NivelAcesso::Comercial->value,
        \App\Enums\NivelAcesso::Assistencia->value,
    ], true);
@endphp
<x-layout :title="$editando ? 'Clientes | Editar' : 'Clientes | Novo'">
    <div
        id="cliente-form-root"
        x-data="{
            tab: '{{ session('tab', 'dados') }}',
            contatos: {{ \Illuminate\Support\Js::from($contatosIniciais) }},
            equipamentos: {{ \Illuminate\Support\Js::from($equipamentosIniciais) }},
            localizacoes: {{ \Illuminate\Support\Js::from($localizacoesIniciais) }},
            lat: {{ \Illuminate\Support\Js::from(old('cli_latitude', $cliente->cli_latitude)) }},
            lng: {{ \Illuminate\Support\Js::from(old('cli_longitude', $cliente->cli_longitude)) }},
            linkMapa: {{ \Illuminate\Support\Js::from(old('cli_link_mapa', $cliente->cli_link_mapa)) }},
            addContato() { this.contatos.push({ nome: '', cargo: '', telefone: '', email: '', tipo: 0 }); },
            removerContato(i) { this.contatos.splice(i, 1); },
            addEquipamento() { this.equipamentos.push({ descricao: '' }); },
            removerEquipamento(i) { this.equipamentos.splice(i, 1); },
            addLocalizacao() { this.localizacoes.push({ descricao: '', link_mapa: '' }); },
            removerLocalizacao(i) { this.localizacoes.splice(i, 1); },
        }"
    >
        <div class="sbadmin-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="sbadmin-page-heading">{{ $editando ? 'Editar Cliente' : 'Novo Cliente' }}</h2>
                <p class="sbadmin-page-subheading">{{ $editando ? $cliente->cli_nome : 'Cadastre um novo cliente no sistema.' }}</p>
            </div>
        </div>

        @if(session('success'))
            <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
        @endif

        <form
            id="form_cliente"
            method="POST"
            action="{{ $editando ? route('clientes.update', $cliente->cli_id) : route('clientes.store') }}"
        >
            @csrf
            @if($editando)
                @method('PUT')
            @endif
            <input type="hidden" name="tab_ativa" x-model="tab">

            <div class="sbadmin-card">
                <div class="sbadmin-card-body">
                    <ul class="nav nav-tabs mb-3 flex-nowrap overflow-x-auto overflow-y-hidden" role="tablist">
                        @foreach([
                            'dados' => 'Dados',
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
                                    placeholder="Ex.: 123.456.789.123 ou ISENTO"
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
                                    placeholder="Ex.: Curitiba"
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
                                    placeholder="Ex.: Maria Souza"
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
                                    placeholder="nome@exemplo.com"
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
                                            <input type="text" class="form-control sbadmin-form-control" maxlength="100" :name="'contatos['+i+'][nome]'" x-model="contato.nome" placeholder="Ex.: João da Silva">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="sbadmin-form-label">Cargo</label>
                                            <input type="text" class="form-control sbadmin-form-control" maxlength="100" :name="'contatos['+i+'][cargo]'" x-model="contato.cargo" placeholder="Ex.: Gerente de Manutenção">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="sbadmin-form-label">Telefone</label>
                                            <input type="text" class="form-control sbadmin-form-control" maxlength="15" :name="'contatos['+i+'][telefone]'" x-model="contato.telefone" oninput="this.value = window.formatarTelefone(this.value)" placeholder="(00) 00000-0000">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="sbadmin-form-label">E-mail</label>
                                            <input type="email" class="form-control sbadmin-form-control" maxlength="100" :name="'contatos['+i+'][email]'" x-model="contato.email" placeholder="nome@exemplo.com">
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
                    {{-- Pedido do cliente (2026-09-14): removido o "Escolher no
                         mapa" (busca via Nominatim + clique num Leaflet embutido)
                         de toda a tela — a busca nunca vai igualar a experiência
                         do app do Google (confirmado testando um endereço real que
                         o OSM não tem cadastrado), e o botão "Abrir no Google Maps"
                         derivado de lat/lng também foi removido por não servir pra
                         nada na prática. Localização agora é só: (a) "Usar minha
                         localização" quando o usuário está fisicamente no lugar
                         (grava lat/lng nos bastidores, ainda usados pelo mapa de
                         relações — CRM07), ou (b) colar o link de um lugar já
                         pesquisado no Google Maps de verdade — mesmo padrão do
                         Roteiro de Viagem. --}}
                    <div x-show="tab === 'geo'" x-cloak>
                        <h6 class="fw-bold">Localização principal</h6>
                        <input type="hidden" id="cli_latitude" name="cli_latitude" x-model.number="lat">
                        <input type="hidden" id="cli_longitude" name="cli_longitude" x-model.number="lng">

                        <div class="row">
                            <div class="col-md-11">
                                <x-sbadmin::form.input
                                    id="cli_link_mapa"
                                    type="url"
                                    name="cli_link_mapa"
                                    label="Link do Google Maps"
                                    maxlength="500"
                                    placeholder="https://maps.app.goo.gl/..."
                                    x-model="linkMapa"
                                />
                            </div>
                            <div class="col-md-1">
                                <label class="sbadmin-form-label d-block">&nbsp;</label>
                                <a
                                    class="btn btn-outline-primary btn-sm w-100"
                                    :class="{ disabled: !linkMapa }"
                                    :href="linkMapa || '#'"
                                    target="_blank"
                                    rel="noopener"
                                    title="Abrir no Google Maps"
                                >
                                    <i class="bi bi-map" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>

                        @if($podeGeolocalizar)
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="btnAtribuirLocalizacao">
                                    <i class="bi bi-geo-alt" aria-hidden="true"></i> Usar minha localização
                                </button>
                            </div>
                            <div id="geo-feedback" class="small text-body-secondary mt-2"></div>

                            <hr>

                            {{-- Pedido do cliente (2026-09-11): lista de localizações
                                 adicionais do mesmo cliente (empresa, instalação/
                                 montagem, manutenção...), cada uma com descrição
                                 livre + link do Google Maps próprio. --}}
                            <h6 class="fw-bold">Outras localizações</h6>
                            <template x-for="(localizacao, i) in localizacoes" :key="i">
                                <div class="sbadmin-card mb-3">
                                    <div class="sbadmin-card-body">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="sbadmin-form-label">Descrição</label>
                                                <input type="text" class="form-control sbadmin-form-control" maxlength="100" :name="'localizacoes['+i+'][descricao]'" x-model="localizacao.descricao" placeholder="Ex.: Empresa, Instalação/Montagem, Manutenção">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="sbadmin-form-label">Link do Google Maps</label>
                                                <input type="url" class="form-control sbadmin-form-control" maxlength="500" :name="'localizacoes['+i+'][link_mapa]'" x-model="localizacao.link_mapa" placeholder="https://maps.app.goo.gl/...">
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end gap-2 mt-2">
                                            <a
                                                class="btn btn-outline-primary btn-sm"
                                                :class="{ disabled: !localizacao.link_mapa }"
                                                :href="localizacao.link_mapa || '#'"
                                                target="_blank"
                                                rel="noopener"
                                                title="Abrir no Google Maps"
                                            >
                                                <i class="bi bi-map" aria-hidden="true"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger btn-sm" @click="removerLocalizacao(i)">
                                                <i class="bi bi-trash" aria-hidden="true"></i> Remover
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <p class="text-body-secondary small" x-show="localizacoes.length === 0">Nenhuma outra localização cadastrada.</p>

                            <button type="button" class="btn btn-outline-primary btn-sm" @click="addLocalizacao()">
                                <i class="bi bi-plus-lg" aria-hidden="true"></i> Adicionar localização
                            </button>
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
                                <button type="button" class="btn btn-outline-danger btn-sm" @click="removerEquipamento(i)" title="Remover">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>
                            </div>
                        </template>
                        <p class="text-body-secondary small" x-show="equipamentos.length === 0">Nenhum equipamento cadastrado.</p>
                        <button type="button" class="btn btn-outline-primary btn-sm mb-4" @click="addEquipamento()">
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
                            placeholder="Descreva o caso de sucesso..."
                        />

                        @if($editando)
                            <p class="text-body-secondary">Histórico de atendimentos e orçamentos deste cliente — disponível a partir da sessão de persistência do Núcleo/CRM.</p>
                        @endif
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

    {{-- Pedido do cliente (2026-09-14): removido o picker de mapa (Leaflet)
         e a busca de endereço (Nominatim) — não iguala a experiência do
         Google Maps de verdade. Só resta "Usar minha localização" (GPS do
         navegador, grava lat/lng nos bastidores pro mapa de relações) e o
         link colável (ver bloco acima). --}}
    @if($podeGeolocalizar)
        @push('scripts')
            <script>
                document.getElementById('btnAtribuirLocalizacao')?.addEventListener('click', function () {
                    const feedback = document.getElementById('geo-feedback');
                    if (!navigator.geolocation) {
                        feedback.textContent = 'Geolocalização não é suportada neste navegador.';
                        return;
                    }
                    if (!window.isSecureContext) {
                        feedback.textContent = 'Este navegador só libera a localização em conexão segura (HTTPS/localhost) — verifique o certificado do site.';
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
                        function (erro) {
                            // Mensagens especificas por codigo (PERMISSION_DENIED=1,
                            // POSITION_UNAVAILABLE=2, TIMEOUT=3) — a mensagem generica
                            // anterior aparecia mesmo quando o navegador nunca chegou
                            // a pedir permissao (ex.: negada permanentemente antes, ou
                            // certificado nao confiavel bloqueando a API em silencio).
                            const mensagens = {
                                1: 'Permissão de localização negada. Se o navegador não perguntou, verifique nas configurações do site (ícone de cadeado na barra de endereço) se a localização já não foi bloqueada antes.',
                                2: 'Não foi possível determinar a localização (sinal de GPS/rede indisponível).',
                                3: 'Tempo esgotado ao tentar obter a localização.',
                            };
                            feedback.textContent = mensagens[erro.code] || 'Não foi possível obter a localização.';
                        }
                    );
                });
            </script>
        @endpush
    @endif
</x-layout>