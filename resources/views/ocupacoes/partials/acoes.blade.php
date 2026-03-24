<button type="button"
    class="btn btn-sm btn-indigo btn-modal-mao-de-obra"
    data-id="{{ $o->ocup_id }}"
    data-tp-id="{{ $o->ocup_tp_ocupacao_id }}"
    data-descricao="{{ e($o->ocup_descricao) }}"
    data-ativo="{{ $o->ocup_ativo }}">
    <i class="fas fa-edit"></i>
</button>