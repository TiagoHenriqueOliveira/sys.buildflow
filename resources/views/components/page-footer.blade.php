<div class="card-footer d-flex justify-content-end">
    @if ($showSave ?? false)
    <button type="button" id="btnAtualizarRelatorio" data-relatorio-id="{{ $relatorioId ?? null }}" class="btn btn-success btn-icon-split mr-2">
        <span class="icon text-white-50">
            <i class="fas fa-save"></i>
        </span>
        <span class="text">{{ $saveText ?? 'Salvar' }}</span>
    </button>
    @endif

    <a href="{{ $backRoute }}" class="btn btn-secondary btn-icon-split">
        <span class="icon text-white-50">
            <i class="fas fa-arrow-left"></i>
        </span>
        <span class="text">Voltar</span>
    </a>
</div>