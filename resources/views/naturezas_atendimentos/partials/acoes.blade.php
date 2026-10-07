<button type="button"
    class="btn btn-sm btn-indigo btn-modal-natureza-atendimento"
    data-id="{{ $n->nat_aten_id }}"
    data-descricao="{{ e($n->nat_aten_descricao) }}"
    data-mod-rel="{{ (int)$n->nat_aten_mod_relatorio_id }}"
    data-ativo="{{ (int)$n->nat_aten_ativo }}">
    <i class="fas fa-edit"></i>
</button>