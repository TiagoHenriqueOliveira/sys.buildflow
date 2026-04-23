{{-- CLIMA --}}
<div class="tab-pane fade" id="tab-clima" role="tabpanel">
    <form id="form_relatorio_clima" data-action="{{ route('atendimentos-relatorios.update-clima', $atendimentoRelatorio->aten_rel_id) }}">
        @csrf
        <div class="row">
            {{-- MANHÃ --}}
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header text-center font-weight-bold">
                        <span>Manhã</span>
                    </div>
                    <div class="card-body">
                        <div class="custom-control custom-radio mb-2">
                            <input type="radio" id="manha_ensolarado" name="clima_manha" class="custom-control-input" value="ensolarado">
                            <label class="custom-control-label" for="manha_ensolarado">
                                <i class="fas fa-sun text-warning mr-1"></i> Ensolarado
                            </label>
                        </div>

                        <div class="custom-control custom-radio mb-2">
                            <input type="radio" id="manha_nublado" name="clima_manha" class="custom-control-input" value="nublado">
                            <label class="custom-control-label" for="manha_nublado">
                                <i class="fas fa-cloud text-secondary mr-1"></i> Nublado
                            </label>
                        </div>

                        <div class="custom-control custom-radio">
                            <input type="radio" id="manha_chuvoso" name="clima_manha" class="custom-control-input" value="chuvoso">
                            <label class="custom-control-label" for="manha_chuvoso">
                                <i class="fas fa-cloud-rain text-primary mr-1"></i> Chuvoso
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TARDE --}}
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header text-center font-weight-bold">
                        <span>Tarde</span>
                    </div>
                    <div class="card-body">
                        <div class="custom-control custom-radio mb-2">
                            <input type="radio" id="tarde_ensolarado" name="clima_tarde" class="custom-control-input" value="ensolarado">
                            <label class="custom-control-label" for="tarde_ensolarado">
                                <i class="fas fa-sun text-warning mr-1"></i> Ensolarado
                            </label>
                        </div>

                        <div class="custom-control custom-radio mb-2">
                            <input type="radio" id="tarde_nublado" name="clima_tarde" class="custom-control-input" value="nublado">
                            <label class="custom-control-label" for="tarde_nublado">
                                <i class="fas fa-cloud text-secondary mr-1"></i> Nublado
                            </label>
                        </div>

                        <div class="custom-control custom-radio">
                            <input type="radio" id="tarde_chuvoso" name="clima_tarde" class="custom-control-input" value="chuvoso">
                            <label class="custom-control-label" for="tarde_chuvoso">
                                <i class="fas fa-cloud-rain text-primary mr-1"></i> Chuvoso
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- NOITE --}}
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header text-center font-weight-bold">
                        <span>Noite</span>
                    </div>
                    <div class="card-body">
                        <div class="custom-control custom-radio mb-2">
                            <input type="radio" id="noite_ensolarado" name="clima_noite" class="custom-control-input" value="ensolarado">
                            <label class="custom-control-label" for="noite_ensolarado">
                                <i class="fas fa-moon text-dark mr-1"></i> Céu limpo
                            </label>
                        </div>

                        <div class="custom-control custom-radio mb-2">
                            <input type="radio" id="noite_nublado" name="clima_noite" class="custom-control-input" value="nublado">
                            <label class="custom-control-label" for="noite_nublado">
                                <i class="fas fa-cloud text-secondary mr-1"></i> Nublado
                            </label>
                        </div>

                        <div class="custom-control custom-radio">
                            <input type="radio" id="noite_chuvoso" name="clima_noite" class="custom-control-input" value="chuvoso">
                            <label class="custom-control-label" for="noite_chuvoso">
                                <i class="fas fa-cloud-rain text-primary mr-1"></i> Chuvoso
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>