<button type="button"
    class="btn btn-sm btn-indigo btn-modal-equipamento"
    data-id="{{ $e->equip_id }}"
    data-descricao="{{ e($e->equip_descricao) }}"
    data-ativo="{{ $e->equip_ativo }}">
    <i class="fas fa-edit"></i>
</button>