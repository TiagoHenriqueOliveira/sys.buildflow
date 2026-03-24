<button type="button"
    class="btn btn-sm btn-indigo btn-modal-usuario"
    data-id="{{ $u->user_id }}"
    data-nome="{{ e($u->user_nome) }}"
    data-email="{{ e($u->user_email) }}"
    data-nivel="{{ $u->user_nivel_acesso }}"
    data-ativo="{{ $u->user_ativo }}">
    <i class="fas fa-edit"></i>
</button>