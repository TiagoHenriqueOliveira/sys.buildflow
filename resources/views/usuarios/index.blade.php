<x-layout title="Usuários">
    <div
        x-data="{
            aberto: {{ $errors->any() ? 'true' : 'false' }},
            editando: {{ old('user_id') ? 'true' : 'false' }},
        }"
    >
        <div class="sbadmin-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="sbadmin-page-heading">Usuários</h2>
                <p class="sbadmin-page-subheading">Gerencie os usuários com acesso ao sistema.</p>
            </div>
            @can('usuarios.editar')
            <button
                type="button"
                class="btn btn-primary sbadmin-btn-primary"
                @click="editando = false; aberto = true; resetFormularioUsuario()"
            >
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Cadastrar
            </button>
            @endcan
        </div>

        @if(session('success'))
            <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
        @endif

        {{-- S-08 (28/11/2026): o código aparece só esta vez — o banco guarda só o HMAC dele. --}}
        @if(session('codigo_gerado'))
            @php($gerado = session('codigo_gerado'))
            <x-sbadmin::alert type="warning">
                Código de {{ $gerado['tipo'] }} de <strong>{{ $gerado['nome'] }}</strong>:
                <code class="fs-5 user-select-all">{{ $gerado['codigo'] }}</code>
                (uso único, vale até {{ $gerado['expira_em'] }}).
                Repasse ao usuário por um canal seguro — ele não será mostrado de novo.
            </x-sbadmin::alert>
        @endif

        @if($errors->has('codigo'))
            <x-sbadmin::alert type="error">{{ $errors->first('codigo') }}</x-sbadmin::alert>
        @endif

        <form method="GET" action="{{ route('usuarios.index') }}" class="sbadmin-card mb-4">
            {{-- Filtros individuais por coluna — combinaveis entre si (AND:
                 cada filtro preenchido restringe ainda mais o resultado).
                 Substituem a busca unica que existia antes (removida a
                 pedido do cliente). --}}
            <div class="sbadmin-card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-5">
                        <label for="f_nome" class="sbadmin-form-label">Nome</label>
                        <input type="text" id="f_nome" name="f_nome" value="{{ $filtroNome }}" class="form-control sbadmin-form-control" placeholder="Nome">
                    </div>
                    <div class="col-6 col-md-5">
                        <label for="f_email" class="sbadmin-form-label">E-mail</label>
                        <input type="text" id="f_email" name="f_email" value="{{ $filtroEmail }}" class="form-control sbadmin-form-control" placeholder="E-mail">
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
            :headers="['Ações', 'Nome', 'E-mail', 'Nível', 'Status']"
            :paginator="$usuarios"
            :count="$usuarios->count()"
            empty-message="Nenhum usuário encontrado."
        >
            @foreach($usuarios as $u)
                <tr class="{{ $u->user_ativo ? '' : 'table-danger' }}">
                    <td class="text-center">
                        {{-- S-12 (D-06): a conta do fornecedor aparece só para leitura (ela se edita em "Minha conta"). --}}
                        @php($somenteLeitura = $u->isProtegido())
                        @if($somenteLeitura)
                            <span class="text-body-secondary small">Só leitura</span>
                        @endif
                        {{-- Administrador só é editável por quem tem acesso total. --}}
                        @if(! $somenteLeitura && auth()->user()->temPermissao('usuarios.editar') && ((int) $u->user_nivel_acesso !== \App\Enums\NivelAcesso::Administrador->value || auth()->user()->temAcessoTotal()))
                        <button
                            type="button"
                            class="btn btn-sm sbadmin-table-action-btn"
                            data-id="{{ $u->user_id }}"
                            data-nome="{{ e($u->user_nome) }}"
                            data-email="{{ e($u->user_email) }}"
                            data-nivel="{{ $u->user_nivel_acesso }}"
                            data-ativo="{{ (int) $u->user_ativo }}"
                            aria-label="Editar {{ e($u->user_nome) }}"
                            @click="editando = true; aberto = true; preencherFormularioUsuario($el.dataset)"
                        >
                            <i class="bi bi-pencil" aria-hidden="true"></i>
                        </button>
                        @endif
                        {{-- 2026-11-27: só acesso total redefine o MFA (celular perdido). --}}
                        @if(! $somenteLeitura && auth()->user()->temAcessoTotal() && $u->temMfaConfigurado())
                        <form method="POST" action="{{ route('usuarios.mfa.redefinir', $u->user_id) }}" class="d-inline" x-ref="mfa{{ $u->user_id }}">
                            @csrf
                            @method('DELETE')
                            <button
                                type="button"
                                class="btn btn-sm sbadmin-table-action-btn"
                                title="Redefinir verificação em duas etapas"
                                aria-label="Redefinir verificação em duas etapas de {{ e($u->user_nome) }}"
                                @click="confirmar('Redefinir a verificação em duas etapas de {{ e($u->user_nome) }}? Os acessos dele serão encerrados e, no próximo login, ele cadastra o autenticador de novo.', { rotulo: 'Redefinir', tipo: 'danger', onConfirm: () => $refs.mfa{{ $u->user_id }}.submit() })"
                            >
                                <i class="bi bi-shield-x" aria-hidden="true"></i>
                            </button>
                        </form>
                        @endif
                        {{-- S-08: sem autenticador → código de ativação; com autenticador → código para redefinir a senha. --}}
                        @if(! $somenteLeitura && auth()->user()->temAcessoTotal() && $u->user_ativo)
                        @php($tipoCodigo = $u->temMfaConfigurado() ? \App\Services\CodigoAcessoService::REDEFINIR_SENHA : \App\Services\CodigoAcessoService::ATIVACAO_MFA)
                        @php($rotuloCodigo = $u->temMfaConfigurado() ? 'Gerar código para redefinir a senha' : 'Gerar código de ativação do autenticador')
                        <form method="POST" action="{{ route('usuarios.codigo-acesso', $u->user_id) }}" class="d-inline" x-ref="cod{{ $u->user_id }}">
                            @csrf
                            <input type="hidden" name="tipo" value="{{ $tipoCodigo }}">
                            <button
                                type="button"
                                class="btn btn-sm sbadmin-table-action-btn"
                                title="{{ $rotuloCodigo }}"
                                aria-label="{{ $rotuloCodigo }} de {{ e($u->user_nome) }}"
                                @click="confirmar('{{ $rotuloCodigo }} de {{ e($u->user_nome) }}? Um código anterior do mesmo tipo deixa de valer.', { rotulo: 'Gerar', onConfirm: () => $refs.cod{{ $u->user_id }}.submit() })"
                            >
                                <i class="bi {{ $u->temMfaConfigurado() ? 'bi-key' : 'bi-qr-code' }}" aria-hidden="true"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                    <td>
                        {{ $u->user_nome }}
                        @unless($u->temMfaConfigurado())
                            <x-sbadmin::badge type="warning">Sem autenticador</x-sbadmin::badge>
                        @endunless
                        @if($u->isProtegido())
                            <x-sbadmin::badge type="info">Fornecedor — acesso total</x-sbadmin::badge>
                            <div class="small text-body-secondary">
                                Último acesso:
                                {{ ($ultimosAcessos[$u->user_id] ?? null) ? \Illuminate\Support\Carbon::parse($ultimosAcessos[$u->user_id])->format('d/m/Y H:i') : 'nunca' }}
                                · sem acesso pelo app · ações registradas na auditoria
                            </div>
                        @endif
                    </td>
                    <td>{{ $u->user_email }}</td>
                    <td>{{ \App\Enums\NivelAcesso::from((int) $u->user_nivel_acesso)->label() }}</td>
                    <td>
                        <x-sbadmin::badge :type="$u->user_ativo ? 'success' : 'error'">
                            {{ $u->user_ativo ? 'Ativo' : 'Inativo' }}
                        </x-sbadmin::badge>
                    </td>
                </tr>
            @endforeach
        </x-sbadmin::table>

        @include('usuarios.modal')
    </div>

    @push('scripts')
        <script>
            function preencherFormularioUsuario(data) {
                document.getElementById('user_id').value = data.id || '';
                document.getElementById('user_nome').value = data.nome || '';
                document.getElementById('user_email').value = data.email || '';
                document.getElementById('user_nivel_acesso').value = data.nivel || '';
                document.getElementById('user_senha').value = '';
                document.getElementById('user_senha_confirmation').value = '';
                document.getElementById('user_ativo').checked = data.ativo === '1';

                document.getElementById('user_method').value = 'PUT';
                document.getElementById('form_usuario').action = '{{ url('/usuarios') }}/' + data.id;
            }

            function resetFormularioUsuario() {
                const form = document.getElementById('form_usuario');
                form.reset();

                document.getElementById('user_id').value = '';
                document.getElementById('user_ativo').checked = true;
                document.getElementById('user_method').value = 'POST';
                form.action = '{{ route('usuarios.store') }}';
            }

            // Sugerir senha provisória (<= 10 caracteres) e preencher a confirmação
            // — porta direta do antigo public/js/app/usuarios.js sem jQuery.
            function gerarSenhaProvisoria(length) {
                const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@#$%';
                length = Math.min(parseInt(length || 10, 10), 10);

                if (window.crypto && window.crypto.getRandomValues) {
                    const array = new Uint32Array(length);
                    window.crypto.getRandomValues(array);
                    return Array.from(array, x => chars[x % chars.length]).join('');
                }

                let senha = '';
                for (let i = 0; i < length; i++) {
                    senha += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                return senha;
            }

            document.addEventListener('click', function (event) {
                if (event.target.closest('#btnSugerirSenha')) {
                    // Política de senha (auditoria 27/11/2026): 10+ caracteres com
                    // letra e número — sorteia de novo até atender.
                    let senha = gerarSenhaProvisoria(10);
                    while (!/[A-Za-z]/.test(senha) || !/[0-9]/.test(senha)) {
                        senha = gerarSenhaProvisoria(10);
                    }
                    document.getElementById('user_senha').value = senha;
                    document.getElementById('user_senha_confirmation').value = senha;
                    document.getElementById('user_senha').focus();
                    return;
                }

                const toggleBtn = event.target.closest('.btn-toggle-password');
                if (toggleBtn) {
                    const input = document.querySelector(toggleBtn.dataset.target);
                    if (!input) return;
                    const icon = toggleBtn.querySelector('i');
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon?.classList.replace('bi-eye', 'bi-eye-slash');
                    } else {
                        input.type = 'password';
                        icon?.classList.replace('bi-eye-slash', 'bi-eye');
                    }
                }
            });
        </script>
    @endpush
</x-layout>
