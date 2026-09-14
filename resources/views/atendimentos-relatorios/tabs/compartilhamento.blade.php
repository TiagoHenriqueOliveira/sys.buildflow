{{-- COMPARTILHAMENTO (BF07) — histórico/comprovante. O disparo do
     compartilhamento em si é majoritariamente mobile (WhatsApp/menu nativo,
     fica pra sessão Android); aqui o painel web permite CONSULTAR o
     comprovante gerado (data/hora + hash) e, como esta tela ainda não tem
     nenhum consumidor mobile rodando, também gerar um comprovante de teste
     pelo próprio painel para o fluxo ser demonstrável nesta etapa. --}}
<div id="tab-compartilhamento" role="tabpanel" x-show="tab === 'compartilhamento'">
    <div class="mb-3">
        <button type="button" id="btnGerarComprovante" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50">
                <i class="bi bi-share" aria-hidden="true"></i>
            </span>
            <span class="text">Compartilhar</span>
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-striped" id="tableCompartilhamentos">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Canal</th>
                    <th>Hash do comprovante</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>