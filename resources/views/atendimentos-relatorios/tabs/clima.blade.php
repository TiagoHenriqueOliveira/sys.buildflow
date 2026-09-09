{{-- CLIMA --}}
<div id="tab-clima" role="tabpanel" x-show="tab === 'clima'">
    <form id="form_relatorio_clima" data-action="{{ route('atendimentos-relatorios.update-clima', $atendimentoRelatorio->aten_rel_id) }}">
        @csrf
        <div class="row">
            {{-- MANHÃ --}}
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header text-center fw-bold">
                        <span>Manhã</span>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-2">
                            <input type="radio" id="manha_ensolarado" name="clima_manha" class="form-check-input" value="ensolarado">
                            <label class="form-check-label" for="manha_ensolarado">
                                <i class="bi bi-sun text-warning me-1" aria-hidden="true"></i> Ensolarado
                            </label>
                        </div>

                        <div class="form-check mb-2">
                            <input type="radio" id="manha_nublado" name="clima_manha" class="form-check-input" value="nublado">
                            <label class="form-check-label" for="manha_nublado">
                                <i class="bi bi-cloud text-secondary me-1" aria-hidden="true"></i> Nublado
                            </label>
                        </div>

                        <div class="form-check">
                            <input type="radio" id="manha_chuvoso" name="clima_manha" class="form-check-input" value="chuvoso">
                            <label class="form-check-label" for="manha_chuvoso">
                                <i class="bi bi-cloud-rain text-primary me-1" aria-hidden="true"></i> Chuvoso
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TARDE --}}
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header text-center fw-bold">
                        <span>Tarde</span>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-2">
                            <input type="radio" id="tarde_ensolarado" name="clima_tarde" class="form-check-input" value="ensolarado">
                            <label class="form-check-label" for="tarde_ensolarado">
                                <i class="bi bi-sun text-warning me-1" aria-hidden="true"></i> Ensolarado
                            </label>
                        </div>

                        <div class="form-check mb-2">
                            <input type="radio" id="tarde_nublado" name="clima_tarde" class="form-check-input" value="nublado">
                            <label class="form-check-label" for="tarde_nublado">
                                <i class="bi bi-cloud text-secondary me-1" aria-hidden="true"></i> Nublado
                            </label>
                        </div>

                        <div class="form-check">
                            <input type="radio" id="tarde_chuvoso" name="clima_tarde" class="form-check-input" value="chuvoso">
                            <label class="form-check-label" for="tarde_chuvoso">
                                <i class="bi bi-cloud-rain text-primary me-1" aria-hidden="true"></i> Chuvoso
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- NOITE --}}
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header text-center fw-bold">
                        <span>Noite</span>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-2">
                            <input type="radio" id="noite_ensolarado" name="clima_noite" class="form-check-input" value="ensolarado">
                            <label class="form-check-label" for="noite_ensolarado">
                                <i class="bi bi-moon-stars text-dark me-1" aria-hidden="true"></i> Céu limpo
                            </label>
                        </div>

                        <div class="form-check mb-2">
                            <input type="radio" id="noite_nublado" name="clima_noite" class="form-check-input" value="nublado">
                            <label class="form-check-label" for="noite_nublado">
                                <i class="bi bi-cloud text-secondary me-1" aria-hidden="true"></i> Nublado
                            </label>
                        </div>

                        <div class="form-check">
                            <input type="radio" id="noite_chuvoso" name="clima_noite" class="form-check-input" value="chuvoso">
                            <label class="form-check-label" for="noite_chuvoso">
                                <i class="bi bi-cloud-rain text-primary me-1" aria-hidden="true"></i> Chuvoso
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
