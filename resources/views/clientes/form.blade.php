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
    $editando = $cliente->exists;
    $contatosIniciais = old('contatos', $editando
        ? $cliente->contatos->map(fn ($c) => [
            'nome' => $c->cli_cont_nome,
            'cargo' => $c->cli_cont_cargo,
            'telefone' => $c->cli_cont_telefone,
            'email' => $c->cli_cont_email,
            'tipo' => $c->cli_cont_tipo->value,
        ])->values()->all()
        : []);
    $isComercial = auth()->user()->user_nivel_acesso === \App\Enums\NivelAcesso::Comercial->value;
@endphp
<x-layout :title="$editando ? 'Clientes | Editar' : 'Clientes | Novo'">
    <div
        x-data="{
            tab: 'dados',
            contatos: {{ \Illuminate\Support\Js::from($contatosIniciais) }},
            lat: {{ \Illuminate\Support\Js::from(old('cli_latitude', $cliente->cli_latitude)) }},
            lng: {{ \Illuminate\Support\Js::from(old('cli_longitude', $cliente->cli_longitude)) }},
            addContato() { this.contatos.push({ nome: '', cargo: '', telefone: '', email: '', tipo: 0 }); },
            removerContato(i) { this.contatos.splice(i, 1); },
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
                    <ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto" role="tablist">
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
                            <div class="col-sm-6">
                                <x-sbadmin::form.input
                                    id="cli_contato_principal"
                                    name="cli_contato_principal"
                                    label="Contato principal"
                                    :value="old('cli_contato_principal', $cliente->cli_contato_principal)"
                                    maxlength="100"
                                />
                            </div>
                            <div class="col-sm-6">
                                <x-sbadmin::form.select
                                    id="cli_vendedor_id"
                                    name="cli_vendedor_id"
                                    label="Vendedor responsável"
                                    :options="$vendedores->pluck('user_nome', 'user_id')->all()"
                                    :value="old('cli_vendedor_id', $cliente->cli_vendedor_id)"
                                    placeholder="Nenhum"
                                />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <x-sbadmin::form.input
                                    id="cli_cnpj"
                                    name="cli_cnpj"
                                    label="CNPJ"
                                    :value="old('cli_cnpj', $cliente->cli_cnpj)"
                                    maxlength="18"
                                    required
                                    placeholder="00.000.000/0000-00"
                                    oninput="this.value = window.formatarCnpj(this.value)"
                                />
                            </div>
                            <div class="col-sm-6">
                                <x-sbadmin::form.input
                                    id="cli_inscricao_estadual"
                                    name="cli_inscricao_estadual"
                                    label="Inscrição Estadual"
                                    :value="old('cli_inscricao_estadual', $cliente->cli_inscricao_estadual)"
                                    maxlength="20"
                                />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-8">
                                <x-sbadmin::form.input
                                    id="cli_cidade"
                                    name="cli_cidade"
                                    label="Cidade"
                                    :value="old('cli_cidade', $cliente->cli_cidade)"
                                    maxlength="100"
                                    required
                                />
                            </div>
                            <div class="col-sm-4">
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
                            <div class="col-sm-6">
                                <x-sbadmin::form.input
                                    id="cli_segmento"
                                    name="cli_segmento"
                                    label="Segmento"
                                    :value="old('cli_segmento', $cliente->cli_segmento)"
                                    maxlength="255"
                                    placeholder="Ex.: Sucroenergético"
                                />
                            </div>
                            <div class="col-sm-6">
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
                        </div>

                        <div class="row">
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
                            <div class="col-sm-4">
                                <x-sbadmin::form.input
                                    id="cli_telefone"
                                    name="cli_telefone"
                                    label="Telefone"
                                    :value="old('cli_telefone', $cliente->cli_telefone)"
                                    maxlength="15"
                                    placeholder="(00) 00000-0000"
                                    oninput="this.value = window.formatarTelefone(this.value)"
                                />
                            </div>
                            <div class="col-sm-4">
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
                        <div class="row">
                            <div class="col-sm-6">
                                <x-sbadmin::form.input id="cli_latitude" name="cli_latitude" label="Latitude" x-model.number="lat" />
                            </div>
                            <div class="col-sm-6">
                                <x-sbadmin::form.input id="cli_longitude" name="cli_longitude" label="Longitude" x-model.number="lng" />
                            </div>
                        </div>

                        @if($isComercial)
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="btnAtribuirLocalizacao">
                                    <i class="bi bi-geo-alt" aria-hidden="true"></i> Atribuir localização
                                </button>
                                <a
                                    class="btn btn-outline-secondary btn-sm"
                                    :class="{ disabled: !lat || !lng }"
                                    :href="lat && lng ? ('https://www.google.com/maps?q=' + lat + ',' + lng) : '#'"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    <i class="bi bi-map" aria-hidden="true"></i> Abrir no Google Maps
                                </a>
                            </div>
                            <div id="geo-feedback" class="small text-body-secondary mt-2"></div>
                        @else
                            <p class="text-body-secondary small">A captura de localização é feita por usuários com perfil Comercial.</p>
                        @endif
                    </div>

                    {{-- ── Histórico consolidado (placeholder — dados reais na Etapa 2) ── --}}
                    <div x-show="tab === 'historico'" x-cloak>
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

    @if($isComercial)
        @push('scripts')
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
                            const root = document.querySelector('[x-data]');
                            Alpine.$data(root).lat = Number(pos.coords.latitude.toFixed(7));
                            Alpine.$data(root).lng = Number(pos.coords.longitude.toFixed(7));
                            feedback.textContent = 'Localização atribuída com sucesso.';
                        },
                        function () {
                            feedback.textContent = 'Não foi possível obter a localização. Verifique a permissão do navegador.';
                        }
                    );
                });
            </script>
        @endpush
    @endif
</x-layout>
