<x-layout title="Orçamentos">
    <div class="sbadmin-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2 class="sbadmin-page-heading">Orçamentos</h2>
            <p class="sbadmin-page-subheading">Gerencie os orçamentos comerciais cadastrados no sistema.</p>
        </div>
        <a href="{{ route('orcamentos.create') }}" class="btn btn-primary sbadmin-btn-primary">
            <i class="bi bi-plus-lg" aria-hidden="true"></i> Cadastrar
        </a>
    </div>

    @if(session('success'))
        <x-sbadmin::alert type="success">{{ session('success') }}</x-sbadmin::alert>
    @endif

    <form method="GET" action="{{ route('orcamentos.index') }}" class="sbadmin-card mb-4">
        <div class="sbadmin-card-body">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label for="f_cliente" class="sbadmin-form-label">Cliente</label>
                    <input type="text" id="f_cliente" name="f_cliente" value="{{ $filtroCliente }}" class="form-control sbadmin-form-control" placeholder="Cliente">
                </div>
                <div class="col-6 col-md-3">
                    <label for="f_vendedor" class="sbadmin-form-label">Vendedor</label>
                    <select id="f_vendedor" name="f_vendedor" class="form-select sbadmin-form-control">
                        <option value="">Todos</option>
                        @foreach($vendedores as $v)
                            <option value="{{ $v->user_id }}" @selected((string) $filtroVendedor === (string) $v->user_id)>{{ $v->user_nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label for="f_tipo" class="sbadmin-form-label">Tipo</label>
                    <select id="f_tipo" name="f_tipo" class="form-select sbadmin-form-control">
                        <option value="">Todos</option>
                        @foreach($tiposOrcamento as $t)
                            <option value="{{ $t->crm_tp_orc_id }}" @selected((string) $filtroTipo === (string) $t->crm_tp_orc_id)>{{ $t->crm_tp_orc_nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="f_nivel" class="sbadmin-form-label">Nível</label>
                    <select id="f_nivel" name="f_nivel" class="form-select sbadmin-form-control">
                        <option value="">Todos</option>
                        @foreach($niveis as $n)
                            <option value="{{ $n->value }}" @selected((string) $filtroNivel === (string) $n->value)>{{ $n->label() }}</option>
                        @endforeach
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
        :headers="['Ações', 'Cliente', 'Vendedor', 'Tipo', 'Nível', 'Prazo de Envio', 'Resultado']"
        :paginator="$orcamentos"
        :count="$orcamentos->count()"
        empty-message="Nenhum orçamento encontrado."
    >
        @foreach($orcamentos as $o)
            <tr class="{{ $o->orc_ativo ? '' : 'table-danger' }}">
                <td class="text-center">
                    <a href="{{ route('orcamentos.edit', $o->orc_id) }}" class="btn btn-sm sbadmin-table-action-btn" aria-label="Editar orçamento">
                        <i class="bi bi-pencil" aria-hidden="true"></i>
                    </a>
                </td>
                <td>{{ optional($o->cliente)->cli_nome }}</td>
                <td>{{ optional($o->vendedor)->user_nome }}</td>
                <td>{{ optional($o->tipoOrcamento)->crm_tp_orc_nome }}</td>
                <td>{{ optional($o->orc_nivel)->label() }}</td>
                <td>{{ optional($o->orc_prazo_envio)->format('d/m/Y') }}</td>
                <td>
                    @if($o->orc_resultado)
                        <x-sbadmin::badge :type="$o->orc_resultado->badgeTipo()">
                            {{ $o->orc_resultado->label() }}
                        </x-sbadmin::badge>
                    @else
                        <x-sbadmin::badge type="neutral">Em aberto</x-sbadmin::badge>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-sbadmin::table>
</x-layout>