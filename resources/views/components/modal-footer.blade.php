<div class="modal-footer p-0 pt-3">
    <button type="submit" class="btn btn-success btn-icon-split">
        <span class="icon text-white-50">
            <i class="fas fa-save"></i>
        </span>
        <span class="text">{{ $saveText ?? 'Salvar' }}</span>
    </button>

    <button type="button" class="btn btn-secondary btn-icon-split" data-dismiss="modal">
        <span class="icon text-white-50">
            <i class="fas fa-times"></i>
        </span>
        <span class="text">{{ $closeText ?? 'Fechar' }}</span>
    </button>
</div>