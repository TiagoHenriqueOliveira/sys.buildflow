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
    {{-- x-data compartilhado pelo botão "Cadastrar", pelos botões de editar
         de cada linha da tabela e pelo modal (clientes/modal.blade.php,
         incluído no fim desta view) — é o que substitui o antigo
         $('#modal_cliente').modal('show') do Bootstrap 4/jQuery. `aberto` e
         `editando` já nascem no estado certo quando a página volta de um
         POST/PUT com erro de validação (old('cli_id') identifica se era uma
         edição), pra reabrir o modal automaticamente com os dados e os
         erros preenchidos pelos próprios componentes <x-sbadmin::form.*>. --}}
    <div
        x-data="{
            aberto: {{ $errors->any() ? 'true' : 'false' }},
            editando: {{ old('cli_id') ? 'true' : 'false' }},
        }"
    >
        <div class="sbadmin-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="sbadmin-page-heading">Clientes</h2>
                <p class="sbadmin-page-subheading">Gerencie os clientes cadastrados no sistema.</p>
            </div>
            <button
                type="button"
                class="btn btn-primary sbadmin-btn-primary"
                @click="editando = false; aberto = true; resetFormularioCliente()"
            >
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Cadastrar
            </button>
        </div>

        @if(session('success'))
            <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
        @endif

        <form method="GET" action="{{ route('clientes.index') }}" class="sbadmin-card mb-4">
            {{-- Filtros individuais por coluna — combinaveis entre si (AND:
                 cada filtro preenchido restringe ainda mais o resultado).
                 Substituem a busca unica que existia antes (removida a
                 pedido do cliente). --}}
            <div class="sbadmin-card-body d-flex flex-wrap gap-2 align-items-end">
                <div style="min-width: 160px;">
                    <label for="f_nome" class="sbadmin-form-label">Nome</label>
                    <input type="text" id="f_nome" name="f_nome" value="{{ $filtroNome }}" class="form-control sbadmin-form-control" placeholder="Nome">
                </div>
                <div style="min-width: 160px;">
                    <label for="f_cnpj" class="sbadmin-form-label">CNPJ</label>
                    <input type="text" id="f_cnpj" name="f_cnpj" value="{{ $filtroCnpj }}" class="form-control sbadmin-form-control" placeholder="CNPJ">
                </div>
                <div style="min-width: 140px;">
                    <label for="f_cidade" class="sbadmin-form-label">Cidade</label>
                    <input type="text" id="f_cidade" name="f_cidade" value="{{ $filtroCidade }}" class="form-control sbadmin-form-control" placeholder="Cidade">
                </div>
                <div style="min-width: 80px;">
                    <label for="f_uf" class="sbadmin-form-label">UF</label>
                    <input type="text" id="f_uf" name="f_uf" value="{{ $filtroUf }}" class="form-control sbadmin-form-control" placeholder="UF" maxlength="2">
                </div>
                <div style="min-width: 150px;">
                    <label for="f_telefone" class="sbadmin-form-label">Telefone</label>
                    <input type="text" id="f_telefone" name="f_telefone" value="{{ $filtroTelefone }}" class="form-control sbadmin-form-control" placeholder="Telefone">
                </div>
                <div style="min-width: 180px;">
                    <label for="f_email" class="sbadmin-form-label">E-mail</label>
                    <input type="text" id="f_email" name="f_email" value="{{ $filtroEmail }}" class="form-control sbadmin-form-control" placeholder="E-mail">
                </div>
                <div style="min-width: 150px;">
                    <label for="f_status" class="sbadmin-form-label">Status</label>
                    <select id="f_status" name="f_status" class="form-select sbadmin-form-control">
                        <option value="">Todos</option>
                        <option value="1" @selected($filtroStatus === '1')>Ativo</option>
                        <option value="0" @selected($filtroStatus === '0')>Desativado</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-info">
                    <i class="bi bi-funnel" aria-hidden="true"></i> Aplicar Filtro
                </button>
                @if($temFiltro)
                    <a href="{{ route('clientes.index') }}" class="btn btn-link">Limpar</a>
                @endif
            </div>
        </form>

        <x-sbadmin::table
            :headers="['Ações', 'Nome', 'CNPJ', 'Cidade', 'UF', 'Telefone', 'E-mail', 'Status']"
            :paginator="$clientes"
            :count="$clientes->count()"
            empty-message="Nenhum cliente encontrado."
        >
            @foreach($clientes as $c)
                <tr class="{{ $c->cli_ativo ? '' : 'table-danger' }}">
                    <td class="text-center">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            data-id="{{ $c->cli_id }}"
                            data-nome="{{ e($c->cli_nome) }}"
                            data-cnpj="{{ e($c->cli_cnpj) }}"
                            data-cidade="{{ e($c->cli_cidade) }}"
                            data-uf="{{ e($c->cli_uf) }}"
                            data-telefone="{{ e($c->cli_telefone) }}"
                            data-email="{{ e($c->cli_email) }}"
                            data-ativo="{{ (int) $c->cli_ativo }}"
                            aria-label="Editar {{ e($c->cli_nome) }}"
                            @click="editando = true; aberto = true; preencherFormularioCliente($el.dataset)"
                        >
                            <i class="bi bi-pencil" aria-hidden="true"></i>
                        </button>
                    </td>
                    <td>{{ $c->cli_nome }}</td>
                    <td>{{ $formatarCnpjExibicao($c->cli_cnpj) }}</td>
                    <td>{{ $c->cli_cidade }}</td>
                    <td>{{ $c->cli_uf }}</td>
                    <td>{{ $formatarTelefoneExibicao($c->cli_telefone) }}</td>
                    <td>{{ $c->cli_email }}</td>
                    <td>
                        <x-sbadmin::badge :type="$c->cli_ativo ? 'success' : 'error'">
                            {{ $c->cli_ativo ? 'Ativo' : 'Desativado' }}
                        </x-sbadmin::badge>
                    </td>
                </tr>
            @endforeach
        </x-sbadmin::table>

        @include('clientes.modal')
    </div>

    @push('scripts')
        <script>
            // Preenche o modal a partir do dataset do botão "editar" da linha
            // clicada (equivalente ao antigo abrirModalCliente() do
            // public/js/app/clientes.js, sem jQuery). O modal em si é
            // aberto/fechado pelo Alpine (ver x-data acima) — aqui só
            // populamos os campos e apontamos o form pro endpoint certo.
            function preencherFormularioCliente(data) {
                document.getElementById('cli_id').value = data.id || '';
                document.getElementById('cli_nome').value = data.nome || '';
                document.getElementById('cli_cnpj').value = window.formatarCnpj(data.cnpj || '');
                document.getElementById('cli_cidade').value = data.cidade || '';
                document.getElementById('cli_uf').value = (data.uf || '').toUpperCase();
                document.getElementById('cli_telefone').value = window.formatarTelefone(data.telefone || '');
                document.getElementById('cli_email').value = data.email || '';
                document.getElementById('cli_ativo').checked = data.ativo === '1';

                document.getElementById('cli_method').value = 'PUT';
                document.getElementById('form_cliente').action = '{{ url('/clientes') }}/' + data.id;
            }

            function resetFormularioCliente() {
                const form = document.getElementById('form_cliente');
                form.reset();

                document.getElementById('cli_id').value = '';
                document.getElementById('cli_ativo').checked = true;
                document.getElementById('cli_method').value = 'POST';
                form.action = '{{ route('clientes.store') }}';
            }
        </script>
    @endpush
</x-layout>
