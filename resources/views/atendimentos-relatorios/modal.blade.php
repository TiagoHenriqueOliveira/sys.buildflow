{{-- Modal "Novo Relatório" — diferente dos demais modais migrados: não tem
     modo edição (só cria; a edição em si acontece na tela
     atendimentos-relatorios/show.blade.php) e agora é um <form> comum com
     redirect+flash (ver AtendimentosRelatoriosController::store()) em vez
     do antigo fetch()/JSON que redirecionava via JS — nada mais consumia
     esse JSON, então dava pra seguir o mesmo padrão de clientes/modal.blade.php. --}}
<div class="modal-backdrop show" x-show="aberto" x-cloak></div>
<div
    class="modal"
    :class="{ show: aberto }"
    x-show="aberto"
    style="display: block"
    x-cloak
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal_relatorio_label"
    @keydown.escape.window="aberto = false"
    @click.self="aberto = false"
>
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_relatorio_label">Relatório de Atendimento</h5>
                <button type="button" class="btn-close" aria-label="Fechar" @click="aberto = false"></button>
            </div>

            <div class="modal-body">
                <form id="form_relatorio" method="POST" action="{{ route('atendimentos-relatorios.store') }}" autocomplete="off">
                    @csrf

                    <div class="sbadmin-form-group">
                        <label for="rel_aten_label" class="sbadmin-form-label">Atendimento</label>
                        <input type="hidden" id="rel_aten_id" name="aten_id" value="{{ old('aten_id') }}">
                        <input
                            type="text"
                            class="form-control sbadmin-form-control @error('aten_id') is-invalid @enderror"
                            id="rel_aten_label"
                            name="aten_label"
                            value="{{ old('aten_label') }}"
                            placeholder="Digite para buscar..."
                            autocomplete="off"
                        >
                        @error('aten_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @else
                            <div class="sbadmin-form-help">Busque pelo nome do cliente.</div>
                        @enderror
                    </div>

                    <x-sbadmin::form.input
                        id="rel_data"
                        type="date"
                        name="aten_rel_data"
                        label="Data do relatório"
                        :value="old('aten_rel_data', now()->format('Y-m-d'))"
                        max="{{ now()->format('Y-m-d') }}"
                    />

                    <div class="modal-footer px-0 pb-0">
                        <button type="submit" class="btn btn-primary sbadmin-btn-primary">
                            <i class="bi bi-check-lg" aria-hidden="true"></i> Salvar
                        </button>
                        <button type="button" class="btn btn-outline-secondary" @click="aberto = false">
                            <i class="bi bi-x-lg" aria-hidden="true"></i> Fechar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
