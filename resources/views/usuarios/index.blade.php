<x-layout title="Usuários">
    <div
        x-data="{
            aberto: {{ $errors->any() ? 'true' : 'false' }},
            editando: {{ old('user_id') ? 'true' : 'false' }},
        }"
    >
        <div class="sbadmin-page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h2 class="sbadmin-page-heading">Usuários</h2>
                <p class="sbadmin-page-subheading">Gerencie os usuários com acesso ao sistema.</p>
            </div>
            <button
                type="button"
                class="btn btn-primary sbadmin-btn-primary"
                @click="editando = false; aberto = true; resetFormularioUsuario()"
            >
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Cadastrar
            </button>
        </div>

        @if(session('success'))
            <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
        @endif

        <form method="GET" action="{{ route('usuarios.index') }}" class="sbadmin-card mb-4">
            <div class="sbadmin-card-body d-flex flex-wrap gap-2 align-items-end">
                <div class="flex-grow-1" style="min-width: 240px;">
                    <label for="busca" class="sbadmin-form-label">Buscar</label>
                    <input
                        type="text"
                        id="busca"
                        name="busca"
                        value="{{ $busca }}"
                        class="form-control sbadmin-form-control"
                        placeholder="Nome ou e-mail"
                    >
                </div>
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="bi bi-search" aria-hidden="true"></i> Buscar
                </button>
                @if($busca !== '')
                    <a href="{{ route('usuarios.index') }}" class="btn btn-link">Limpar</a>
                @endif
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
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
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
                    </td>
                    <td>{{ $u->user_nome }}</td>
                    <td>{{ $u->user_email }}</td>
                    <td>{{ (int) $u->user_nivel_acesso === 0 ? 'Administrador' : 'Técnico' }}</td>
                    <td>
                        <x-sbadmin::badge :type="$u->user_ativo ? 'success' : 'error'">
                            {{ $u->user_ativo ? 'Ativo' : 'Desativado' }}
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
                    const senha = gerarSenhaProvisoria(10);
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
