<button type="button"
    class="btn btn-sm btn-indigo btn-modal-ocorrencia"
    data-id="{{ $o->ocor_id }}"
    data-descricao="{{ e($o->ocor_descricao) }}"
    data-ativo="{{ $o->ocor_ativo }}">
    <i class="fas fa-edit"></i>
</button>