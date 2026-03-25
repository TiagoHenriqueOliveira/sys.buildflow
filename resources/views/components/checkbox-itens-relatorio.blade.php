<fieldset class="border p-3">
    <legend class="w-auto px-2">Itens do Relatório</legend>

    <div class="row">
        <div class="col-md-6">
            @foreach([
                'mod_rel_anexo' => 'Anexo',
                'mod_rel_atividade' => 'Atividade',
                'mod_rel_checklist' => 'Checklist',
                'mod_rel_comentario' => 'Comentário',
                'mod_rel_cond_clima' => 'Condição Climática',
                'mod_rel_controle_material' => 'Controle de Material',
            ] as $id => $label)
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="{{ $id }}" name="{{ $id }}" value="1">
                <label class="custom-control-label" for="{{ $id }}">{{ $label }}</label>
            </div>
            @endforeach
        </div>

        <div class="col-md-6">
            @foreach([
                'mod_rel_equipamento' => 'Equipamento',
                'mod_rel_foto' => 'Foto',
                'mod_rel_horarios' => 'Horários',
                'mod_rel_ocorrencia' => 'Ocorrência',
                'mod_rel_ocupacao' => 'Ocupação',
                'mod_rel_video' => 'Vídeo',
            ] as $id => $label)
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="{{ $id }}" name="{{ $id }}" value="1">
                <label class="custom-control-label" for="{{ $id }}">{{ $label }}</label>
            </div>
            @endforeach
        </div>
    </div>
</fieldset>