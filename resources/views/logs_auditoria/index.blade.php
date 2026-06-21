<x-layout title="Logs de Auditoria">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <p class="h5 font-weight-bold text-primary text-decoration-underline mb-0">Logs de Auditoria</p>
        </div>
        <div class="card-body">

            {{-- Filtros --}}
            <form method="GET" class="form-inline mb-4 flex-wrap gap-2">
                <div class="form-group mr-2 mb-2">
                    <label class="mr-1 font-weight-bold">Módulo:</label>
                    <select name="modulo" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        @foreach($modulos as $m)
                            <option value="{{ $m }}" {{ request('modulo') == $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mr-2 mb-2">
                    <label class="mr-1 font-weight-bold">Ação:</label>
                    <select name="acao" class="form-control form-control-sm">
                        <option value="">Todas</option>
                        @foreach(['CRIAR','EDITAR','INATIVAR','APROVAR','CONCLUIR'] as $acao)
                            <option value="{{ $acao }}" {{ request('acao') == $acao ? 'selected' : '' }}>{{ $acao }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mr-2 mb-2">
                    <label class="mr-1 font-weight-bold">De:</label>
                    <input type="date" name="data_de" class="form-control form-control-sm" value="{{ request('data_de') }}">
                </div>
                <div class="form-group mr-2 mb-2">
                    <label class="mr-1 font-weight-bold">Até:</label>
                    <input type="date" name="data_ate" class="form-control form-control-sm" value="{{ request('data_ate') }}">
                </div>
                <button type="submit" class="btn btn-primary btn-sm mb-2">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="{{ route('logs-auditoria.index') }}" class="btn btn-secondary btn-sm mb-2 ml-1">
                    <i class="fas fa-times"></i> Limpar
                </a>
            </form>

            {{-- Tabela de Auditoria --}}
            <h6 class="font-weight-bold mb-2">Auditoria de Ações</h6>
            <div class="table-responsive mb-5">
                <table class="table table-sm table-striped table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Data/Hora</th>
                            <th>Módulo</th>
                            <th>Ação</th>
                            <th>ID Registro</th>
                            <th>Usuário</th>
                            <th>Dados Anteriores</th>
                            <th>Dados Novos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td class="text-nowrap">{{ \Carbon\Carbon::parse($log->log_aud_created_at)->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $log->log_aud_modulo }}</td>
                            <td><span class="badge badge-info">{{ $log->log_aud_acao }}</span></td>
                            <td>{{ $log->log_aud_registro_id }}</td>
                            <td>{{ $log->log_aud_usuario_id }}</td>
                            <td>
                                @if($log->log_aud_dados_anteriores)
                                <details><summary>Ver</summary>
                                <pre class="small mb-0">{{ json_encode($log->log_aud_dados_anteriores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                                @else —
                                @endif
                            </td>
                            <td>
                                @if($log->log_aud_dados_novos)
                                <details><summary>Ver</summary>
                                <pre class="small mb-0">{{ json_encode($log->log_aud_dados_novos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                                @else —
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted">Nenhum registro encontrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $logs->links() }}

            {{-- Tabela de Erros --}}
            <h6 class="font-weight-bold mb-2 mt-4">Erros do Sistema</h6>
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Data/Hora</th>
                            <th>Nível</th>
                            <th>Módulo</th>
                            <th>Mensagem</th>
                            <th>Usuário</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($erros as $erro)
                        <tr>
                            <td class="text-nowrap">{{ \Carbon\Carbon::parse($erro->log_err_created_at)->format('d/m/Y H:i:s') }}</td>
                            <td><span class="badge badge-danger">{{ $erro->log_err_nivel }}</span></td>
                            <td>{{ $erro->log_err_modulo ?? '—' }}</td>
                            <td>{{ $erro->log_err_mensagem }}</td>
                            <td>{{ $erro->log_err_usuario_id ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted">Nenhum erro registrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-layout>
