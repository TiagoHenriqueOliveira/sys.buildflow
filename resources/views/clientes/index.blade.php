@php
    // Formatação de exibição (CNPJ/telefone) feita aqui no servidor — antes
    // ficava a cargo do render() de cada coluna da DataTables no
    // public/js/app/clientes.js (removido nesta migração).
    $formatarCnpjExibicao = function (?string $valor): string {
        if (! $valor || strlen($valor) !== 14) {
            return (string) $valor;
        }

        return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $valor);
    };

    $formatarTelefoneExibicao = function (?string $valor): string {
        return match (strlen((string) $valor)) {
            11 => preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $valor),
            10 => preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $valor),
            default => (string) $valor,
        };
    };
@endphp
<x-layout title="Clientes">
    <div class="sbadmin-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2 class="sbadmin-page-heading">Clientes</h2>
            <p class="sbadmin-page-subheading">Gerencie os clientes cadastrados no sistema.</p>
        </div>
        <a href="{{ route('clientes.create') }}" class="btn btn-primary sbadmin-btn-primary">
            <i class="bi bi-plus-lg" aria-hidden="true"></i> Cadastrar
        </a>
    </div>

    @if(session('success'))
        <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
    @endif

    <form method="GET" action="{{ route('clientes.index') }}" class="sbadmin-card mb-4">
        {{-- Filtros individuais por coluna — combinaveis entre si (AND:
             cada filtro preenchido restringe ainda mais o resultado). --}}
        <div class="sbadmin-card-body">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label for="f_nome" class="sbadmin-form-label">Nome</label>
                    <input type="text" id="f_nome" name="f_nome" value="{{ $filtroNome }}" class="form-control sbadmin-form-control" placeholder="Nome">
                </div>
                <div class="col-6 col-md-2">
                    <label for="f_cnpj" class="sbadmin-form-label">CNPJ</label>
                    <input type="text" id="f_cnpj" name="f_cnpj" value="{{ $filtroCnpj }}" class="form-control sbadmin-form-control" placeholder="CNPJ">
                </div>
                <div class="col-6 col-md-2">
                    <label for="f_cidade" class="sbadmin-form-label">Cidade</label>
                    <input type="text" id="f_cidade" name="f_cidade" value="{{ $filtroCidade }}" class="form-control sbadmin-form-control" placeholder="Cidade">
                </div>
                <div class="col-6 col-md-1">
                    <label for="f_uf" class="sbadmin-form-label">UF</label>
                    <input type="text" id="f_uf" name="f_uf" value="{{ $filtroUf }}" class="form-control sbadmin-form-control" placeholder="UF" maxlength="2">
                </div>
                <div class="col-6 col-md-2">
                    <label for="f_segmento" class="sbadmin-form-label">Segmento</label>
                    <input type="text" id="f_segmento" name="f_segmento" value="{{ $filtroSegmento }}" class="form-control sbadmin-form-control" placeholder="Segmento">
                </div>
                <div class="col-6 col-md-2">
                    <label for="f_classificacao" class="sbadmin-form-label">Classificação</label>
                    <select id="f_classificacao" name="f_classificacao" class="form-select sbadmin-form-control">
                        <option value="">Todas</option>
                        @foreach($classificacoes as $classificacao)
                            <option value="{{ $classificacao->cla_cli_id }}" @selected((string) $filtroClassificacao === (string) $classificacao->cla_cli_id)>{{ $classificacao->cla_cli_nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-1">
                    <label for="f_status" class="sbadmin-form-label">Status</label>
                    <select id="f_status" name="f_status" class="form-select sbadmin-form-control">
                        <option value="">Todos</option>
                        <option value="1" @selected($filtroStatus === '1')>Ativo</option>
                        <option value="0" @selected($filtroStatus === '0')>Inativo</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-info">
                        <i class="bi bi-funnel" aria-hidden="true"></i> Aplicar
                    </button>
                </div>
            </div>
        </div>
    </form>

    <x-sbadmin::table
        :headers="['Ações', 'Nome', 'CNPJ', 'Cidade', 'UF', 'Segmento', 'Classificação', 'Telefone', 'Status']"
        :paginator="$clientes"
        :count="$clientes->count()"
        empty-message="Nenhum cliente encontrado."
    >
        @foreach($clientes as $c)
            <tr class="{{ $c->cli_ativo ? '' : 'table-danger' }}">
                <td class="text-center">
                    <a href="{{ route('clientes.edit', $c->cli_id) }}" class="btn btn-sm sbadmin-table-action-btn" aria-label="Editar {{ e($c->cli_nome) }}">
                        <i class="bi bi-pencil" aria-hidden="true"></i>
                    </a>
                </td>
                <td>{{ $c->cli_nome }}</td>
                <td>{{ $formatarCnpjExibicao($c->cli_cnpj) }}</td>
                <td>{{ $c->cli_cidade }}</td>
                <td>{{ $c->cli_uf }}</td>
                <td>{{ $c->cli_segmento }}</td>
                <td>{{ optional($c->classificacao)->cla_cli_nome }}</td>
                <td>{{ $formatarTelefoneExibicao($c->cli_telefone) }}</td>
                <td>
                    <x-sbadmin::badge :type="$c->cli_ativo ? 'success' : 'error'">
                        {{ $c->cli_ativo ? 'Ativo' : 'Inativo' }}
                    </x-sbadmin::badge>
                </td>
            </tr>
        @endforeach
    </x-sbadmin::table>
</x-layout>
