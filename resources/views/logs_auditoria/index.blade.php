<x-layout title="Logs de Auditoria">
    <div class="sbadmin-page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h2 class="sbadmin-page-heading">Logs de Auditoria</h2>
            <p class="sbadmin-page-subheading">Consulte o histórico de ações e os erros registrados no sistema.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('logs-auditoria.index') }}" class="sbadmin-card mb-4">
        <div class="sbadmin-card-body d-flex flex-wrap gap-2 align-items-end">
            <div style="min-width: 200px;">
                <x-sbadmin::form.select
                    id="modulo"
                    name="modulo"
                    label="Módulo"
                    :options="$modulos->mapWithKeys(fn ($m) => [$m => $m])->all()"
                    :value="request('modulo')"
                    placeholder="Todos"
                />
            </div>
            <div style="min-width: 200px;">
                <x-sbadmin::form.select
                    id="acao"
                    name="acao"
                    label="Ação"
                    :options="collect(['CRIAR','EDITAR','INATIVAR','APROVAR','CONCLUIR'])->mapWithKeys(fn ($a) => [$a => $a])->all()"
                    :value="request('acao')"
                    placeholder="Todas"
                />
            </div>
            <div>
                <x-sbadmin::form.input id="data_de" type="date" name="data_de" label="De" :value="request('data_de')" />
            </div>
            <div>
                <x-sbadmin::form.input id="data_ate" type="date" name="data_ate" label="Até" :value="request('data_ate')" />
            </div>
            <button type="submit" class="btn btn-primary sbadmin-btn-primary">
                <i class="bi bi-search" aria-hidden="true"></i> Filtrar
            </button>
            <a href="{{ route('logs-auditoria.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg" aria-hidden="true"></i> Limpar
            </a>
        </div>
    </form>

    <h5 class="sbadmin-page-heading mb-2" style="font-size: 1.1rem;">Auditoria de Ações</h5>
    <x-sbadmin::table
        :headers="['Data/Hora', 'Módulo', 'Ação', 'ID Registro', 'Usuário', 'Dados Anteriores', 'Dados Novos']"
        :paginator="$logs"
        :count="$logs->count()"
        empty-message="Nenhum registro encontrado."
    >
        @foreach($logs as $log)
            <tr>
                <td class="text-nowrap">{{ \Carbon\Carbon::parse($log->log_aud_created_at)->format('d/m/Y H:i:s') }}</td>
                <td>{{ $log->log_aud_modulo }}</td>
                <td><x-sbadmin::badge type="info">{{ $log->log_aud_acao }}</x-sbadmin::badge></td>
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
        @endforeach
    </x-sbadmin::table>

    <h5 class="sbadmin-page-heading mb-2 mt-5" style="font-size: 1.1rem;">Erros do Sistema</h5>
    <x-sbadmin::table
        :headers="['Data/Hora', 'Nível', 'Módulo', 'Mensagem', 'Usuário']"
        :count="$erros->count()"
        empty-message="Nenhum erro registrado."
    >
        @foreach($erros as $erro)
            <tr>
                <td class="text-nowrap">{{ \Carbon\Carbon::parse($erro->log_err_created_at)->format('d/m/Y H:i:s') }}</td>
                <td><x-sbadmin::badge type="error">{{ $erro->log_err_nivel }}</x-sbadmin::badge></td>
                <td>{{ $erro->log_err_modulo ?? '—' }}</td>
                <td>{{ $erro->log_err_mensagem }}</td>
                <td>{{ $erro->log_err_usuario_id ?? '—' }}</td>
            </tr>
        @endforeach
    </x-sbadmin::table>
</x-layout>
