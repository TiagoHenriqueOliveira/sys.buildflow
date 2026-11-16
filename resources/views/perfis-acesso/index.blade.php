{{-- Perfis de Acesso (2026-11-16). Matriz tela x perfil com "Ver" e
     "Editar". Marcar Editar marca Ver; desmarcar Ver desmarca Editar (o
     backend repete a regra em Permissoes::normalizar). Checkboxes nativos
     com name="permissoes[<nivel>][]" — o Alpine só sincroniza o par. --}}
@php
    $marcado = fn (int $nivel, string $chave) => in_array($chave, $concedidas[$nivel] ?? [], true);
@endphp
<x-layout title="Perfis de Acesso">
    <div class="sbadmin-page-header">
        <h2 class="sbadmin-page-heading">Perfis de Acesso</h2>
        <p class="sbadmin-page-subheading">Defina o que cada perfil pode visualizar e cadastrar/editar no sistema (web e app).</p>
    </div>

    @if(session('success'))
        <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
    @endif

    <x-sbadmin::alert type="info">
        O perfil <strong>Administrador</strong> tem acesso total automático, inclusive a funções novas — não precisa ser configurado.
        "Editar" inclui "Visualizar". Telas não marcadas somem do menu do perfil.
    </x-sbadmin::alert>

    <form
        method="POST"
        action="{{ route('perfis-acesso.update') }}"
        class="sbadmin-card"
        @change="
            const cb = $event.target;
            if (!cb.dataset.par) return;
            const par = document.getElementById(cb.dataset.par);
            if (!par) return;
            if (cb.dataset.acao === 'editar' && cb.checked) par.checked = true;
            if (cb.dataset.acao === 'ver' && !cb.checked) par.checked = false;
        "
        x-data
    >
        @csrf
        @method('PUT')
        <div class="sbadmin-card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th rowspan="2" class="align-middle">Tela</th>
                            @foreach($perfis as $perfil)
                                <th colspan="2" class="text-center border-start">{{ $perfil->label() }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach($perfis as $perfil)
                                <th class="text-center small fw-normal border-start">Visualizar</th>
                                <th class="text-center small fw-normal">Editar</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($telas as $grupo => $telasDoGrupo)
                            <tr class="table-active">
                                <th colspan="{{ 1 + count($perfis) * 2 }}" class="small text-uppercase">{{ $grupo }}</th>
                            </tr>
                            @foreach($telasDoGrupo as $tela => [$rotulo, $acoes])
                                <tr>
                                    <td>{{ $rotulo }}</td>
                                    @foreach($perfis as $perfil)
                                        @php
                                            $idVer = "perm_{$perfil->value}_{$tela}_ver";
                                            $idEditar = "perm_{$perfil->value}_{$tela}_editar";
                                        @endphp
                                        <td class="text-center border-start">
                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                id="{{ $idVer }}"
                                                name="permissoes[{{ $perfil->value }}][]"
                                                value="{{ $tela }}.ver"
                                                data-acao="ver"
                                                @if(in_array('editar', $acoes, true)) data-par="{{ $idEditar }}" @endif
                                                aria-label="{{ $perfil->label() }}: visualizar {{ $rotulo }}"
                                                @checked($marcado($perfil->value, "{$tela}.ver"))
                                            >
                                        </td>
                                        <td class="text-center">
                                            @if(in_array('editar', $acoes, true))
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    id="{{ $idEditar }}"
                                                    name="permissoes[{{ $perfil->value }}][]"
                                                    value="{{ $tela }}.editar"
                                                    data-acao="editar"
                                                    data-par="{{ $idVer }}"
                                                    aria-label="{{ $perfil->label() }}: cadastrar/editar {{ $rotulo }}"
                                                    @checked($marcado($perfil->value, "{$tela}.editar"))
                                                >
                                            @else
                                                <span class="text-body-secondary" aria-hidden="true">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach

                        <tr class="table-active">
                            <th colspan="{{ 1 + count($perfis) * 2 }}" class="small text-uppercase">Papéis</th>
                        </tr>
                        <tr>
                            <td>
                                Atua como vendedor
                                <div class="sbadmin-form-help mt-0">Aparece nos campos de vendedor (orçamento, roteiro, cliente, mapa).</div>
                            </td>
                            @foreach($perfis as $perfil)
                                <td colspan="2" class="text-center border-start">
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        name="permissoes[{{ $perfil->value }}][]"
                                        value="{{ \App\Support\Permissoes::VENDEDOR }}"
                                        aria-label="{{ $perfil->label() }}: atua como vendedor"
                                        @checked($marcado($perfil->value, \App\Support\Permissoes::VENDEDOR))
                                    >
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-lg" aria-hidden="true"></i> Salvar
                </button>
            </div>
        </div>
    </form>
</x-layout>
