{{-- ANEXOS --}}
<div class="tab-pane fade" id="tab-anexos">
    <div class="accordion" id="anexosAccordion">
        <div class="card">
            <div class="card-header" id="headingArquivos">
                <h2 class="mb-0">
                    <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapseArquivos" aria-expanded="true" aria-controls="collapseArquivos">
                        Arquivos
                    </button>
                </h2>
            </div>
            <div id="collapseArquivos" class="collapse show" aria-labelledby="headingArquivos" data-parent="#anexosAccordion">
                <div class="card-body">
                    {{-- Conteúdo de arquivos --}}
                    <p class="text-muted">Selecione arquivos ou use o botão de upload para adicionar anexos.</p>
                    <div class="file-upload-box mb-2">
                        <div class="file-upload-group">
                            <button type="button" class="btn btn-outline-primary file-upload-button upload-trigger" data-input-id="uploadArquivosInput" aria-label="Upload de arquivos">
                                <i class="fas fa-upload"></i>
                            </button>
                            <input id="uploadArquivosInput" name="arquivos[]" type="file" class="file-upload-input" multiple>
                            <span class="file-upload-text">Nenhum arquivo selecionado</span>
                        </div>
                    </div>
                        <div id="anexosArquivosList" class="mt-2">
                            @if(!empty($atendimentoRelatorio->anexos) && $atendimentoRelatorio->anexos->count())
                                <h6>Anexos</h6>
                                <ul class="list-unstyled">
                                    @foreach($atendimentoRelatorio->anexos as $anexo)
                                        <li class="d-flex align-items-center justify-content-between mb-1">
                                            <a href="{{ asset('midia/' . $anexo->aten_rel_anexo_path) }}" target="_blank">{{ basename($anexo->aten_rel_anexo_path) }}</a>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-anexo" data-type="arquivo" data-id="{{ $anexo->aten_rel_anexo_id }}" aria-label="Excluir anexo">
                                                <i class="fas fa-trash"></i>
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
                    <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseFotos" aria-expanded="false" aria-controls="collapseFotos">
                        Fotos
                    </button>
                </h2>
            </div>
            <div id="collapseFotos" class="collapse" aria-labelledby="headingFotos" data-parent="#anexosAccordion">
                <div class="card-body">
                    {{-- Conteúdo de fotos --}}
                    <p class="text-muted">Use o botão abaixo para anexar imagens ao relatório.</p>
                    <div class="file-upload-box mb-2">
                        <div class="file-upload-group">
                            <button type="button" class="btn btn-outline-primary file-upload-button upload-trigger" data-input-id="uploadFotosInput" aria-label="Upload de fotos">
                                <i class="fas fa-upload"></i>
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
                                        $thumb = 'midia/' . preg_replace('#/fotos/#', '/fotos/thumbs/', $foto->aten_rel_foto_path);
                                        $src = asset($thumb);
                                        if (!file_exists(public_path($thumb))) {
                                            $src = asset('midia/' . $foto->aten_rel_foto_path);
                                        }
                                    @endphp
                                    <div class="m-1 position-relative" style="width:120px;">
                                        <a href="#" class="anexo-thumb" data-type="image" data-src="{{ asset('midia/' . $foto->aten_rel_foto_path) }}">
                                            <img src="{{ $src }}" style="width:120px;height:80px;object-fit:cover;border-radius:.35rem;" alt="foto">
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger btn-delete-anexo position-absolute" style="top:4px;right:4px;" data-type="foto" data-id="{{ $foto->aten_rel_foto_id }}" aria-label="Excluir foto">
                                            <i class="fas fa-trash"></i>
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
                    <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseVideos" aria-expanded="false" aria-controls="collapseVideos">
                        Vídeos
                    </button>
                </h2>
            </div>
            <div id="collapseVideos" class="collapse" aria-labelledby="headingVideos" data-parent="#anexosAccordion">
                <div class="card-body">
                    {{-- Conteúdo de vídeos --}}
                    <p class="text-muted">Use o botão abaixo para anexar vídeos ao relatório.</p>
                    <div class="file-upload-box mb-2">
                        <div class="file-upload-group">
                            <button type="button" class="btn btn-outline-primary file-upload-button upload-trigger" data-input-id="uploadVideosInput" aria-label="Upload de vídeos">
                                <i class="fas fa-upload"></i>
                            </button>
                            <input id="uploadVideosInput" name="videos[]" type="file" class="file-upload-input" accept="video/*" multiple>
                            <span class="file-upload-text">Nenhum vídeo selecionado</span>
                        </div>
                    </div>
                        <div id="anexosVideosList" class="mt-2">
                            @if(!empty($atendimentoRelatorio->videos) && $atendimentoRelatorio->videos->count())
                                <h6>Vídeos</h6>
                            @endif
                            <div class="d-flex flex-wrap" id="anexosVideosContainer">
                                @if(!empty($atendimentoRelatorio->videos) && $atendimentoRelatorio->videos->count())
                                    @foreach($atendimentoRelatorio->videos as $video)
                                    @php
                                        $thumb = 'midia/' . preg_replace('#/videos/#', '/videos/thumbs/', $video->aten_rel_vid_path) . '.jpg';
                                        $thumbUrl = asset($thumb);
                                        if (!file_exists(public_path($thumb))) {
                                            $thumbUrl = asset('img/video-placeholder.svg');
                                        }
                                    @endphp
                                    <div class="m-1 position-relative" style="width:160px;">
                                        <a href="#" class="anexo-thumb" data-type="video" data-src="{{ asset('midia/' . $video->aten_rel_vid_path) }}">
                                            <img src="{{ $thumbUrl }}" style="width:160px;height:90px;object-fit:cover;border-radius:.35rem;" alt="video">
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger btn-delete-anexo position-absolute" style="top:4px;right:4px;" data-type="video" data-id="{{ $video->aten_rel_vid_id }}" aria-label="Excluir vídeo">
                                            <i class="fas fa-trash"></i>
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

<!-- Modal de visualização de anexo -->
<div class="modal fade" id="anexoPreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div id="anexoPreviewContent"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
        $(document).on('click', '.anexo-thumb', function (e) {
                e.preventDefault();
                const type = $(this).data('type');
                const src = $(this).data('src');
                const container = $('#anexoPreviewContent');
                container.empty();

                if (type === 'image') {
                        const img = $('<img>').attr('src', src).css({'max-width':'100%','height':'auto'});
                        container.append(img);
                } else if (type === 'video') {
                        const video = $("<video controls playsinline style='width:100%;height:auto;'></video>");
                        video.append($('<source>').attr('src', src));
                        container.append(video);
                }

                $('#anexoPreviewModal').modal('show');
        });
</script>
@endpush