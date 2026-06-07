<a href="{{ route('atendimentos-relatorios.show', $relatorio->aten_rel_id) }}"
    class="btn btn-sm btn-primary"
    title="Visualizar relatório">
    <i class="fas fa-eye"></i>
</a>
<a href="{{ route('atendimentos-relatorios.pdf', $relatorio->aten_rel_id) }}"
    class="btn btn-sm btn-danger"
    title="Gerar PDF"
    target="_blank">
    <i class="fas fa-file-pdf"></i>
</a>