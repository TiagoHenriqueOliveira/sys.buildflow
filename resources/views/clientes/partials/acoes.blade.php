<button type="button"
    class="btn btn-sm btn-indigo btn-modal-cliente"
    data-id="{{ $c->cli_id }}"
    data-nome="{{ e($c->cli_nome) }}"
    data-unidade="{{ e($c->cli_unidade) }}"
    data-cnpj="{{ e($c->cli_cnpj) }}"
    data-cidade="{{ e($c->cli_cidade) }}"
    data-uf="{{ e($c->cli_uf) }}"
    data-telefone="{{ e($c->cli_telefone) }}"
    data-email="{{ e($c->cli_email) }}"
    data-ativo="{{ (int)$c->cli_ativo }}">
    <i class="fas fa-edit"></i>
</button>