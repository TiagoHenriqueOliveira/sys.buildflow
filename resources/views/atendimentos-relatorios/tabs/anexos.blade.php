{{-- ANEXOS — accordion convertido de Bootstrap4 JS (data-toggle="collapse")
     pra Alpine (x-collapse, plugin @alpinejs/collapse já registrado em
     packages/sbadmin/resources/js/sbadmin/app.js e usado no mesmo padrão em
     sidebar-item.blade.php), já que este stack não carrega o bundle JS do
     Bootstrap — só Alpine.js. O preview de foto/vídeo usa o modal
     compartilhado de show.blade.php (#anexoPreviewContent + evento
     `relatorio-preview`); o modal/local antigo com jQuery foi removido daqui
     pra não duplicar o id #anexoPreviewContent. --}}
<div id="tab-anexos" role="tabpanel" x-show="tab === 'anexos'" x-data="{ anexosAberto: 'arquivos' }">
    <div class="accordion" id="anexosAccordion">
        <div class="card">
            <div class="card-header" id="headingArquivos">
                <h2 class="mb-0">
                    <button class="btn btn-link w-100 text-start" type="button"
                        @click="anexosAberto = (anexosAberto === 'arquivos' ? null : 'arquivos')"
                        :aria-expanded="(anexosAberto === 'arquivos').toString()" aria-controls="collapseArquivos">
                        Arquivos
                    </button>
                </h2>
            </div>
            <div id="collapseArquivos" x-show="anexosAberto === 'arquivos'" x-collapse aria-labelledby="headingArquivos">
                <div class="card-body">
                    {{-- Sessao 08 - pedido do cliente: novo upload de arquivos
                         desativado, so fotos daqui pra frente. Lista abaixo
                         mostra os arquivos ja enviados antes desta mudanca. --}}
                    <p class="text-muted">Upload de novos arquivos desativado — use a aba Fotos. Os itens abaixo foram enviados antes desta mudança.</p>
                        <div id="anexosArquivosList" class="mt-2">
                            @if(!empty($atendimentoRelatorio->anexos) && $atendimentoRelatorio->anexos->count())
                                <h6>Anexos</h6>
                                <ul class="list-unstyled">
                                    @foreach($atendimentoRelatorio->anexos as $anexo)
                                        <li class="d-flex align-items-center justify-content-between mb-1">
                                            <a href="{{ asset('midia/' . $anexo->aten_rel_anexo_path) }}" target="_blank">{{ basename($anexo->aten_rel_anexo_path) }}</a>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-anexo" data-type="arquivo" data-id="{{ $anexo->aten_rel_anexo_id }}" aria-label="Excluir anexo">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header" id="headingFotos">
                <h2 class="mb-0">
                    <button class="btn btn-link w-100 text-start" type="button"
                        @click="anexosAberto = (anexosAberto === 'fotos' ? null : 'fotos')"
                        :aria-expanded="(anexosAberto === 'fotos').toString()" aria-controls="collapseFotos">
                        Fotos
                    </button>
                </h2>
            </div>
            <div id="collapseFotos" x-show="anexosAberto === 'fotos'" x-collapse aria-labelledby="headingFotos">
                <div class="card-body">
                    {{-- Conteúdo de fotos --}}
                    <p class="text-muted">Use o botão abaixo para anexar imagens ao relatório.</p>
                    <div class="file-upload-box mb-2">
                        <div class="file-upload-group">
                            <button type="button" class="btn btn-outline-primary file-upload-button upload-trigger" data-input-id="uploadFotosInput" aria-label="Upload de fotos">
                                <i class="bi bi-upload" aria-hidden="true"></i>
                            </button>
                            <input id="uploadFotosInput" name="fotos[]" type="file" class="file-upload-input" accept="image/*" multiple>
                            <span class="file-upload-text">Nenhuma foto selecionada</span>
                        </div>
                    </div>
                        <div id="anexosFotosList" class="mt-2">
                            @if(!empty($atendimentoRelatorio->fotos) && $atendimentoRelatorio->fotos->count())
                                <h6>Fotos</h6>
                            @endif
                            <div class="d-flex flex-wrap" id="anexosFotosContainer">
                                @if(!empty($atendimentoRelatorio->fotos) && $atendimentoRelatorio->fotos->count())
                                    @foreach($atendimentoRelatorio->fotos as $foto)
                                    @php
                                        $thumbPath = preg_replace('#/fotos/#', '/fotos/thumbs/', $foto->aten_rel_foto_path);
                                        $src = \Illuminate\Support\Facades\Storage::disk('public')->exists($thumbPath)
                                            ? asset('midia/' . $thumbPath)
                                            : asset('midia/' . $foto->aten_rel_foto_path);
                                    @endphp
                                    <div class="m-1 position-relative" style="width:120px;">
                                        <a href="#" class="anexo-thumb" data-type="image" data-src="{{ asset('midia/' . $foto->aten_rel_foto_path) }}">
                                            <img src="{{ $src }}" style="width:120px;height:80px;object-fit:cover;border-radius:.35rem;" alt="foto">
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger btn-delete-anexo position-absolute" style="top:4px;right:4px;" data-type="foto" data-id="{{ $foto->aten_rel_foto_id }}" aria-label="Excluir foto">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header" id="headingVideos">
                <h2 class="mb-0">
                    <button class="btn btn-link w-100 text-start" type="button"
                        @click="anexosAberto = (anexosAberto === 'videos' ? null : 'videos')"
                        :aria-expanded="(anexosAberto === 'videos').toString()" aria-controls="collapseVideos">
                        Vídeos
                    </button>
                </h2>
            </div>
            <div id="collapseVideos" x-show="anexosAberto === 'videos'" x-collapse aria-labelledby="headingVideos">
                <div class="card-body">
                    {{-- Sessao 08 - pedido do cliente: novo upload de vídeos
                         desativado. Lista abaixo mostra os vídeos já enviados
                         antes desta mudança. --}}
                    <p class="text-muted">Upload de novos vídeos desativado. Os itens abaixo foram enviados antes desta mudança.</p>
                        <div id="anexosVideosList" class="mt-2">
                            @if(!empty($atendimentoRelatorio->videos) && $atendimentoRelatorio->videos->count())
                                <h6>Vídeos</h6>
                            @endif
                            <div class="d-flex flex-wrap" id="anexosVideosContainer">
                                @if(!empty($atendimentoRelatorio->videos) && $atendimentoRelatorio->videos->count())
                                    @foreach($atendimentoRelatorio->videos as $video)
                                    @php
                                        $thumbPath = preg_replace('#/videos/#', '/videos/thumbs/', $video->aten_rel_vid_path) . '.jpg';
                                        $thumbUrl = \Illuminate\Support\Facades\Storage::disk('public')->exists($thumbPath)
                                            ? asset('midia/' . $thumbPath)
                                            : asset('img/video-placeholder.svg');
                                    @endphp
                                    <div class="m-1 position-relative" style="width:160px;">
                                        <a href="#" class="anexo-thumb" data-type="video" data-src="{{ asset('midia/' . $video->aten_rel_vid_path) }}">
                                            <img src="{{ $thumbUrl }}" style="width:160px;height:90px;object-fit:cover;border-radius:.35rem;" alt="video">
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger btn-delete-anexo position-absolute" style="top:4px;right:4px;" data-type="video" data-id="{{ $video->aten_rel_vid_id }}" aria-label="Excluir vídeo">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                </div>
            </div>
        </div>
    </div>
</div>
