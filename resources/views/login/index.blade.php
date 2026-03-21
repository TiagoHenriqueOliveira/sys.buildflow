<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        
        <title>Login | Sys.Buildflow</title>

        <!-- Bootstrap CSS -->
        <link href="{{ asset('css/bootstrap/bootstrap.css') }}" rel="stylesheet">
        <link href="{{ asset('css/bootstrap/jquery-ui.min.css') }}" rel="stylesheet">
        <link href="{{ asset('css/bootstrap/jquery-ui.theme.css') }}" rel="stylesheet">
        <!-- DataTables CSS -->
        <link href="{{ asset('css/datatables/dataTables.bootstrap4.css') }}" rel="stylesheet">
        <link href="{{ asset('css/datatables/responsive.dataTables.css') }}" rel="stylesheet">
        <!-- Fontawesome CSS -->
        <link href="{{ asset('css/fontawesome-free/all.css') }}" rel="stylesheet">
        <!-- Tema SB Admin 2 -->
        <link href="{{ asset('css/sb-admin-2.css') }}" rel="stylesheet">
        <style>
            body {
                height: 100vh;
                display: flex;
                align-items: center;
                background-color: #d4dbe9;
            }

            .login-card {
                max-width: 400px;
                margin: 0 auto;
                padding: 2rem;
                border-radius: 15px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
                background: #ffffff;
            }

            .logo {
                text-align: center;
                margin-bottom: 2rem;
            }

            .brand-name {
                font-size: 2.2rem;
                line-height: 1;
                margin-bottom: 1.2rem;
                font-weight: 700;
                color: #0d6efd;
            }

            .brand-subtitle {
                font-size: 1.1rem;
                margin-bottom: 1rem;
                color: #6c757d;
            }

            .form-label {
                font-weight: 500;
            }

            .form-control:focus {
                box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
                border-color: #86b7fe;
            }

            .btn-primary {
                font-weight: 600;
            }

            .loading {
                opacity: 0.7;
                pointer-events: none;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <div class="login-card">
                <div class="logo">
                    <div class="brand-name">Sys.Buildflow</div>
                    <div class="brand-subtitle">Gestão de Obras, Manutenção & Assistência Técnica</div>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $error)
                            {{ $error }}<br>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}" id="loginForm">
                    @csrf

                    <div class="mb-3">
                        <label for="usuario" class="form-label">Usuário</label>
                        <input type="text"
                            id="usuario"
                            class="form-control text-uppercase"
                            name="usuario"
                            value="{{ old('usuario') }}"
                            required
                            autofocus
                            autocomplete="username">
                    </div>

                    <div class="mb-3">
                        <label for="senha" class="form-label">Senha</label>
                        <input type="password"
                            id="senha"
                            class="form-control"
                            name="senha"
                            required
                            autocomplete="current-password">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2" id="loginButton">
                        <span id="buttonText">Entrar</span>
                        <span id="loadingSpinner"
                            class="spinner-border spinner-border-sm d-none ms-2"
                            role="status"
                            aria-hidden="true"></span>
                    </button>
                </form>
            </div>
        </div>

        <script>
            document.getElementById('loginForm').addEventListener('submit', function () {
                const button = document.getElementById('loginButton');
                const buttonText = document.getElementById('buttonText');
                const spinner = document.getElementById('loadingSpinner');

                button.classList.add('loading');
                buttonText.textContent = 'Autenticando...';
                spinner.classList.remove('d-none');
                button.disabled = true;
            });
        </script>
    </body>
</html>