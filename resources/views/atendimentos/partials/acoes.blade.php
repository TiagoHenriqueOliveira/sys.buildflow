<button type="button"
    class="btn btn-sm btn-indigo btn-modal-atendimento"
    data-id="{{ $a->aten_id }}"
    data-tp="{{ (int) optional($a->natureza)->nat_aten_tp_atendimento_id }}"
    data-natureza-id="{{ $a->aten_natureza_id }}"
    data-cliente-id="{{ $a->aten_cliente_id }}"
    data-cliente="{{ e(optional($a->cliente)->cli_nome) }}"
    data-usuario-id="{{ $a->aten_usuario_id }}"
    data-status="{{ (int)$a->aten_status }}"
    data-nr-proposta="{{ e($a->aten_nr_proposta) }}"
    data-obra="{{ e($a->aten_descricao) }}"
    data-responsavel="{{ e($a->aten_responsavel) }}"
    data-endereco="{{ e($a->aten_endereco) }}"
    data-dt-inicio="{{ $a->aten_dt_inicio->format('Y-m-d') }}"
    data-dt-fim="{{ $a->aten_dt_fim->format('Y-m-d') }}">
    <i class="fas fa-edit"></i>
</button>