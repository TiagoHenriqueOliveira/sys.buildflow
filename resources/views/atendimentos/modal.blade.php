{{-- Modal de criação/edição de atendimento — bem mais rico que os demais
     modais migrados (clientes, ocorrencias, etc): tem 4 abas (Dados,
     Observações, Equipamentos, Anexos) e PRECISA continuar submetendo via
     fetch()/JSON (não vira <form> com redirect+flash como as telas simples)
     porque ao criar um atendimento novo o backend devolve o aten_id na hora
     e o modal reabre em modo edição SEM FECHAR, liberando na hora as abas
     de Observações/Equipamentos/Anexos pra continuar o cadastro — é assim
     que a tela funciona hoje (ver antigo public/js/app/atendimentos.js) e
     perder isso seria uma regressão de funcionalidade, não só de template.
     Toda a lógica de fetch/estado fica em atendimentos/index.blade.php
     (@push('scripts')); aqui só o HTML/Alpine de abertura e troca de aba. --}}
<div class="modal-backdrop show" x-show="aberto" x-cloak></div>
<div
    class="modal"
    :class="{ show: aberto }"
    :style="aberto ? 'display: block' : 'display: none'"
    x-cloak
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal_atendimento_label"
    @keydown.escape.window="fecharModalAtendimento()"
    @click.self="fecharModalAtendimento()"
>
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_atendimento_label" x-text="editando ? 'Atendimentos | Editar' : 'Atendimentos | Novo'"></h5>
                <button type="button" class="btn-close" aria-label="Fechar" @click="fecharModalAtendimento()"></button>
            </div>

            <div class="modal-body">
                <div id="atendimento-feedback" class="mb-2"></div>

                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link" :class="{ active: tab === 'dados' }" @click="tab = 'dados'">Dados</button>
                    </li>
                    <li class="nav-item">
                        <button
                            type="button"
                            id="tab-observacoes-tab"
                            data-label="Observações"
                            class="nav-link aten-tab-restrita"
                            :class="{ active: tab === 'observacoes', disabled: !editando }"
                            :disabled="!editando"
                            @click="editando && (tab = 'observacoes')"
                        >Observações</button>
                    </li>
                    <li class="nav-item">
                        <button
                            type="button"
                            id="tab-equipamentos-tab"
                            data-label="Equipamentos"
                            class="nav-link aten-tab-restrita"
                            :class="{ active: tab === 'equipamentos', disabled: !editando }"
                            :disabled="!editando"
                            @click="editando && (tab = 'equipamentos')"
                        >Equipamentos</button>
                    </li>
                    <li class="nav-item">
                        <button
                            type="button"
                            id="tab-anexos-aten-tab"
                            data-label="Anexos"
                            class="nav-link aten-tab-restrita"
                            :class="{ active: tab === 'anexos', disabled: !editando }"
                            :disabled="!editando"
                            @click="editando && (tab = 'anexos')"
                        >Anexos</button>
                    </li>
                </ul>

                {{-- Formulário envolve Dados + Observações para compartilhar o mesmo submit (igual ao original) --}}
                <form id="form_atendimento" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="aten_method" value="POST">
                    <input type="hidden" name="aten_id" id="aten_id">

                    <div x-show="tab === 'dados'">
                        <x-sbadmin::form.select
                            id="aten_natureza_id"
                            name="aten_natureza_id"
                            label="Natureza"
                            :options="$naturezasAtendimentos->pluck('nat_aten_descricao', 'nat_aten_id')->all()"
                            placeholder="Selecione..."
                            required
                        />

                        <x-sbadmin::form.select
                            id="aten_usuario_id"
                            name="aten_usuario_id"
                            label="Técnico"
                            :options="$usuarios->pluck('user_nome', 'user_id')->all()"
                            placeholder="Selecione..."
                        />

                        <div class="sbadmin-form-group">
                            <label for="aten_cliente_nome" class="sbadmin-form-label">Cliente</label>
                            <input type="hidden" id="aten_cliente_id" name="aten_cliente_id">
                            <input
                                type="text"
                                class="form-control sbadmin-form-control"
                                id="aten_cliente_nome"
                                placeholder="Digite o nome do cliente"
                                autocomplete="off"
                            >
                        </div>

                        {{-- BF03 — dados do cliente vinculado, sem navegação adicional. --}}
                        <div id="cliente-resumo-box" class="sbadmin-card mb-3" hidden>
                            <div class="sbadmin-card-body py-2 small">
                                <div><strong>Segmento:</strong> <span id="cliente-resumo-segmento">—</span></div>
                                <div><strong>Classificação:</strong> <span id="cliente-resumo-classificacao">—</span></div>
                                <div><strong>Contato principal:</strong> <span id="cliente-resumo-contato">—</span></div>
                                <div><strong>Cidade/UF:</strong> <span id="cliente-resumo-cidade">—</span></div>
                            </div>
                        </div>

                        <x-sbadmin::form.input id="aten_nr_proposta" name="aten_nr_proposta" label="Nº Proposta" maxlength="20" />
                        <x-sbadmin::form.input id="aten_contato" name="aten_contato" label="Contato" maxlength="50" />
                        <x-sbadmin::form.input id="aten_responsavel" name="aten_responsavel" label="Responsável" maxlength="50" />

                        <div class="row align-items-end">
                            <div class="col-sm-9">
                                <x-sbadmin::form.input
                                    id="aten_telefone"
                                    name="aten_telefone"
                                    label="Telefone"
                                    maxlength="20"
                                    placeholder="(00) 00000-0000"
                                    oninput="this.value = window.formatarTelefone(this.value)"
                                />
                            </div>
                            <div class="col-sm-3 mb-3">
                                <a id="btnWhatsapp" href="#" target="_blank" class="btn btn-success w-100" title="Abrir WhatsApp">
                                    <i class="bi bi-whatsapp" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>

                        <x-sbadmin::form.input id="aten_endereco" name="aten_endereco" label="Endereço" maxlength="100" />

                        <div class="sbadmin-form-group">
                            <label class="sbadmin-form-label">Entrega Téc.</label>
                            <div class="form-check form-switch">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    role="switch"
                                    id="aten_entrega_tecnica"
                                    name="aten_entrega_tecnica"
                                    value="1"
                                    x-model="entregaTecnica"
                                >
                                <label class="form-check-label" for="aten_entrega_tecnica">
                                    <span class="sbadmin-badge sbadmin-badge-pill" :class="entregaTecnica ? 'sbadmin-badge-success' : 'sbadmin-badge-neutral'" x-text="entregaTecnica ? 'Sim' : 'Não'">Não</span>
                                </label>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <x-sbadmin::form.input id="aten_dt_inicio" type="date" name="aten_dt_inicio" label="Início" />
                            </div>
                            <div class="col-sm-6">
                                <x-sbadmin::form.input id="aten_dt_fim" type="date" name="aten_dt_fim" label="Fim" />
                            </div>
                        </div>

                        <div class="sbadmin-form-group">
                            <label class="sbadmin-form-label">Status</label>
                            <div class="pt-1">
                                @foreach([0 => 'Não iniciada', 1 => 'Paralisada', 2 => 'Em andamento', 3 => 'Concluída'] as $val => $label)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" id="aten_status_{{ $val }}" name="aten_status" value="{{ $val }}" {{ $val === 0 ? 'checked' : '' }}>
                                        <label class="form-check-label" for="aten_status_{{ $val }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div x-show="tab === 'observacoes'" x-cloak>
                        <x-sbadmin::form.textarea id="aten_obs_tecnica" name="aten_obs_tecnica" label="Técnicas" rows="4" />
                        <x-sbadmin::form.textarea id="aten_obs_cliente" name="aten_obs_cliente" label="Cliente" rows="4" />
                        <x-sbadmin::form.textarea id="aten_obs_manutencao" name="aten_obs_manutencao" label="Manutenção" rows="4" />
                    </div>

                    <div class="modal-footer px-0 pb-0" x-show="tab === 'dados' || tab === 'observacoes'">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg" aria-hidden="true"></i> Salvar
                        </button>
                        <button type="button" class="btn btn-outline-secondary" @click="fecharModalAtendimento()">
                            <i class="bi bi-x-lg" aria-hidden="true"></i> Fechar
                        </button>
                    </div>
                </form>

                {{-- Aba Equipamentos --}}
                <div x-show="tab === 'equipamentos'" x-cloak>
                    <div class="sbadmin-form-group">
                        <label for="aten_equip_descricao" class="sbadmin-form-label">Descrição</label>
                        <input type="text" class="form-control sbadmin-form-control" id="aten_equip_descricao" maxlength="255" placeholder="Ex.: Ar condicionado">
                    </div>

                    <div class="text-end mb-3">
                        <button type="button" id="btnAdicionarEquipamento" class="btn btn-success btn-sm">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i> Adicionar
                        </button>
                    </div>

                    <hr>

                    <h6 class="fw-bold">Equipamentos Cadastrados</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover" id="table_equipamentos">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 50px;">Ações</th>
                                    <th>Descrição</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <div class="modal-footer px-0 pb-0">
                        <button type="button" class="btn btn-outline-secondary" @click="fecharModalAtendimento()">
                            <i class="bi bi-x-lg" aria-hidden="true"></i> Fechar
                        </button>
                    </div>
                </div>

                {{-- Aba Anexos --}}
                <div x-show="tab === 'anexos'" x-cloak>
                    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                        <label class="btn btn-primary btn-sm mb-0">
                            <i class="bi bi-upload" aria-hidden="true"></i>
                            <input type="file" class="d-none" id="aten_anexo_file" multiple>
                        </label>
                        <span class="text-body-secondary small" id="aten_anexo_nome">Selecione arquivos para upload</span>
                        <button type="button" class="btn btn-success btn-sm" id="btnUploadAnexoAten">
                            <i class="bi bi-cloud-upload" aria-hidden="true"></i> Enviar
                        </button>
                    </div>
                    <div id="aten_anexos_lista"></div>

                    <div class="modal-footer px-0 pb-0">
                        <button type="button" class="btn btn-outline-secondary" @click="fecharModalAtendimento()">
                            <i class="bi bi-x-lg" aria-hidden="true"></i> Fechar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
