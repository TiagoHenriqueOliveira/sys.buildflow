<button type="button"
    class="btn btn-sm btn-indigo btn-modal-modelo-relatorio"
    data-id="{{ $m->mod_rel_id }}"
    data-descricao="{{ e($m->mod_rel_descricao) }}"
    data-tp-data="{{ (int)$m->mod_rel_tp_data }}"
    data-ativo="{{ (int)$m->mod_rel_ativo }}"
    data-entrega-tecnica="{{ (int)$m->mod_rel_entrega_tecnica }}">
    <i class="fas fa-edit"></i>
</button>
