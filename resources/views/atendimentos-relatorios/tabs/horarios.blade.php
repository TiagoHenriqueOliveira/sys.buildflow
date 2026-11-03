{{-- HORÁRIOS — pedido do cliente (2026-11-03): um relatório pode cobrir o
     período todo do atendimento, então a aba é uma lista de dias (data, dia
     da semana e horários daquele dia). Relatório diário usa uma linha só.
     Os nomes dias[i][campo] chegam ao backend como array via FormData →
     URLSearchParams (ver o botão "Atualizar" em show.blade.php). --}}
@php
    $diasHorario = $atendimentoRelatorio->horariosDias->map->paraJson()->values();
    $dataRelatorioIso = $atendimentoRelatorio->aten_rel_data->format('Y-m-d');
@endphp
<div id="tab-horarios" role="tabpanel" x-show="tab === 'horarios'">
    <form
        id="form_relatorio_horarios"
        data-action="{{ route('atendimentos-relatorios.update-horarios', $atendimentoRelatorio->aten_rel_id) }}"
        x-data="horariosRelatorio(@js($diasHorario), @js($dataRelatorioIso), @js(now()->format('Y-m-d')))"
        @horarios-carregados.window="definirDias($event.detail)"
    >
        @csrf
        <template x-for="(dia, i) in dias" :key="dia.chave">
            <div class="row g-2 align-items-end mb-2">
                <div class="col-6 col-lg-2">
                    <label class="sbadmin-form-label" :class="{ 'd-lg-none': i > 0 }" :for="`hora_data_${i}`">Data</label>
                    <input type="date" class="form-control sbadmin-form-control" :id="`hora_data_${i}`" :name="`dias[${i}][data]`" x-model="dia.data" :max="hoje" required>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="sbadmin-form-label" :class="{ 'd-lg-none': i > 0 }" :for="`hora_dia_semana_${i}`">Dia da semana</label>
                    <input type="text" class="form-control sbadmin-form-control" :id="`hora_dia_semana_${i}`" :value="diaSemana(dia.data)" readonly tabindex="-1">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="sbadmin-form-label" :class="{ 'd-lg-none': i > 0 }" :for="`hora_entrada_${i}`">Entrada</label>
                    <input type="time" class="form-control sbadmin-form-control" :id="`hora_entrada_${i}`" :name="`dias[${i}][entrada]`" x-model="dia.entrada">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="sbadmin-form-label" :class="{ 'd-lg-none': i > 0 }" :for="`hora_ini_int_${i}`">Início intervalo</label>
                    <input type="time" class="form-control sbadmin-form-control" :id="`hora_ini_int_${i}`" :name="`dias[${i}][inicio_intervalo]`" x-model="dia.inicio_intervalo">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="sbadmin-form-label" :class="{ 'd-lg-none': i > 0 }" :for="`hora_fim_int_${i}`">Fim intervalo</label>
                    <input type="time" class="form-control sbadmin-form-control" :id="`hora_fim_int_${i}`" :name="`dias[${i}][fim_intervalo]`" x-model="dia.fim_intervalo">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="sbadmin-form-label" :class="{ 'd-lg-none': i > 0 }" :for="`hora_saida_${i}`">Saída</label>
                    <div class="input-group">
                        <input type="time" class="form-control sbadmin-form-control" :id="`hora_saida_${i}`" :name="`dias[${i}][saida]`" x-model="dia.saida">
                        <button type="button" class="btn btn-outline-danger" title="Remover dia" aria-label="Remover dia" @click="removerDia(i)" :disabled="dias.length === 1">
                            <i class="bi bi-trash" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
            <button type="button" class="btn btn-outline-primary" @click="adicionarDia()">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Adicionar dia
            </button>
            <span class="sbadmin-form-help mt-0">Relatório de vários dias: um dia por linha. Clique em "Atualizar" para gravar.</span>
        </div>
    </form>
</div>

@push('scripts')
    <script>
        function horariosRelatorio(diasIniciais, dataRelatorio, hoje) {
            const DIAS_SEMANA = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
            let sequencia = 0;
            const novoDia = (data, base = {}) => ({
                chave: ++sequencia,
                data: data || '',
                entrada: base.entrada || '',
                inicio_intervalo: base.inicio_intervalo || '',
                fim_intervalo: base.fim_intervalo || '',
                saida: base.saida || '',
            });

            return {
                dias: [],
                hoje,

                init() {
                    this.definirDias(diasIniciais);
                },

                definirDias(lista) {
                    this.dias = (lista && lista.length)
                        ? lista.map((d) => novoDia(d.data, d))
                        : [novoDia(dataRelatorio)];
                },

                diaSemana(data) {
                    if (!data) return '';
                    const [a, m, d] = data.split('-').map(Number);
                    return DIAS_SEMANA[new Date(a, m - 1, d).getDay()] || '';
                },

                // Sugere o dia seguinte ao último informado (sem passar de
                // hoje) e repete os horários dele, que costumam ser iguais.
                adicionarDia() {
                    const ultimo = this.dias[this.dias.length - 1];
                    let proxima = '';
                    if (ultimo?.data) {
                        const [a, m, d] = ultimo.data.split('-').map(Number);
                        const dt = new Date(a, m - 1, d + 1);
                        const iso = [dt.getFullYear(), String(dt.getMonth() + 1).padStart(2, '0'), String(dt.getDate()).padStart(2, '0')].join('-');
                        proxima = iso <= this.hoje ? iso : '';
                    }
                    this.dias.push(novoDia(proxima, ultimo || {}));
                },

                removerDia(i) {
                    if (this.dias.length > 1) this.dias.splice(i, 1);
                },
            };
        }
    </script>
@endpush
