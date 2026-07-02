<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="">
        <meta name="author" content="">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? '' }} | Sys.Buildflow</title>
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/favicon-32x32.png') }}">

        <!-- CSS -->
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
        <!-- CSS personalizado -->
        <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
    </head>

    <body id="page-top">
        <!-- Page Wrapper -->
        <div id="wrapper">
            <!-- Sidebar -->
            <ul class="navbar-nav bg-gradient-darkblue sidebar sidebar-dark accordion toggled" id="accordionSidebar">
                <!-- Nav Item - Dashboard -->
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/dashboard') }}">
                        <i class="fas fa-chart-line fa-fw fa-lg"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <!-- Divider -->
                <hr class="sidebar-divider">

                <!-- Nav Item - Atendimentos -->
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/atendimentos') }}">
                        <i class="fas fa-headset fa-fw fa-lg"></i>
                        <span>Atendimentos</span>
                    </a>
                </li>

                @if(Auth::user()->user_nivel_acesso === 0)
                <!-- Nav Item - Clientes -->
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/clientes') }}">
                        <i class="fas fa-address-card fa-fw fa-lg"></i>
                        <span>Clientes</span>
                    </a>
                </li>

                @endif

                <!-- Nav Item - Relatórios -->
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/atendimentos-relatorios') }}">
                        <i class="fas fa-chart-bar fa-fw fa-lg"></i>
                        <span>Relatórios</span>
                    </a>
                </li>

                <!-- Nav Item - Configurações Collapse Menu -->
                @if(Auth::user()->user_nivel_acesso === 0)
                <li class="nav-item">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePages" aria-expanded="true" aria-controls="collapsePages">
                        <i class="fas fa-cog fa-fw fa-lg"></i>
                        <span>Configurações</span>
                    </a>
                    <div id="collapsePages" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="{{ url('/modelos-de-relatorios') }}">
                            <i class="fas fa-file-alt fa-fw fa-lg mr-2"></i>Modelos de Relatórios
                        </a>
                        <a class="collapse-item" href="{{ url('/naturezas-dos-atendimentos') }}">
                            <i class="fas fa-tags fa-fw fa-lg mr-2"></i>Naturezas de Atendimentos
                        </a>
                        <a class="collapse-item" href="{{ url('/ocorrencias') }}">
                            <i class="fas fa-exclamation-triangle fa-fw fa-lg mr-2"></i>Ocorrências
                        </a>
                        <a class="collapse-item" href="{{ url('/usuarios') }}">
                            <i class="fas fa-users fa-fw fa-lg mr-2"></i>Usuários
                        </a>
                        <a class="collapse-item" href="{{ url('/logs-auditoria') }}">
                            <i class="fas fa-history fa-fw fa-lg mr-2"></i>Logs de Auditoria
                        </a>
                        </div>
                    </div>
                </li>
                @endif

                <!-- Divider -->
                <hr class="sidebar-divider d-none d-md-block">

            </ul>
            <!-- End of Sidebar -->

            <!-- Content Wrapper -->
            <div id="content-wrapper" class="d-flex flex-column">
                <!-- Main Content -->
                <div id="content">
                    <!-- Topbar -->
                    <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                        <!-- Sidebar Toggle (Topbar) -->
                        <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                            <i class="fa fa-bars"></i>
                        </button>

                        <!-- Topbar Navbar -->
                        <ul class="navbar-nav ml-auto">
                            @if(file_exists(public_path('downloads/apk/buildflow.apk')))
                            <!-- Nav Item - Download APK -->
                            <li class="nav-item">
                                <a class="nav-link" href="{{ asset('downloads/apk/buildflow.apk') }}" download title="Baixar App Android">
                                    <i class="fas fa-android fa-fw"></i>
                                    <span class="text-gray-600 small ml-1">Baixar App</span>
                                </a>
                            </li>
                            @endif
                            <div class="topbar-divider d-none d-sm-block"></div>

                            <!-- Nav Item - User Information -->
                            <li class="nav-item dropdown no-arrow">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <span class="mr-2 text-gray-600 small">{{ Auth::user()->user_nome }}</span>
                                </a>
                                <!-- Dropdown - User Information -->
                                <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                        <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                        Sair
                                    </a>
                                </div>
                            </li>
                        </ul>
                    </nav>
                    <!-- End of Topbar -->

                    <!-- Begin Page Content -->
                    <div class="container-fluid">

                        {{ $slot }}

                    </div>
                    <!-- /.container-fluid -->
                </div>
                <!-- End of Main Content -->

                <!-- Footer -->
                <footer class="sticky-footer bg-white">
                    <div class="container my-auto">
                        <div class="copyright text-center my-auto">
                            <span>Sys.Buildflow - Copyright &copy; <?php echo date('Y'); ?></span>
                        </div>
                    </div>
                </footer>
                <!-- End of Footer -->

            </div>
            <!-- End of Content Wrapper -->

        </div>
        <!-- End of Page Wrapper -->

        <!-- Scroll to Top Button-->
        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>

        <!-- Logout Modal-->
        <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Sair do sistema</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>

                    <div class="modal-body">Deseja encerrar sua sessão?</div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancelar</button>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">Sair</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- JavaScript -->
        <script src="{{ asset('js/jquery/jquery.js') }}"></script>
        <script src="{{ asset('js/jquery/jquery-ui.min.js') }}"></script>
        <script src="{{ asset('js/bootstrap/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('js/jquery-easing/jquery.easing.min.js') }}"></script>
        <script src="{{ asset('js/sb-admin-2.min.js') }}"></script>

        <!-- DataTables -->
        <script src="{{ asset('js/datatables/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('js/datatables/dataTables.bootstrap4.min.js') }}"></script>
        <script src="{{ asset('js/datatables/dataTables.responsive.min.js') }}"></script>
        <script src="{{ asset('js/datatables/dataTables.buttons.min.js') }}"></script>
        <script src="{{ asset('js/datatables/buttons.html5.min.js') }}"></script>
        <script src="{{ asset('js/datatables/jszip.min.js') }}"></script>

        <!-- Utils.js -->
        <script src="{{ asset('js/app/utils.js') }}"></script>

        <!-- Outros plugins -->
        <script src="{{ asset('js/jquery-mask/jquery.mask.js') }}"></script>
        <script src="{{ asset('js/jquery-mask/mask.js') }}"></script>
        <script src="{{ asset('js/bootstrap-notify/bootstrap-notify.min.js') }}"></script>

        <!-- Base URL -->
        <script>
            var baseURL = '{{ url("/") }}';
        </script>

        <!-- Scritps do App -->
        @stack('scripts')
    </body>
</html>