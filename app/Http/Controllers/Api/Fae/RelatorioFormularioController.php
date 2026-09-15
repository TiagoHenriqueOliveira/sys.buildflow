<?php

namespace App\Http\Controllers\Api\Fae;

use App\Http\Controllers\Controller;
use App\Models\AtendimentoRelatorio;
use App\Models\AtendimentoRelatorioResposta;
use App\Models\AtendimentoRelatorioRespostaFoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * BF05/NC03 (mobile) - motor de formulario dinamico. Reaproveita a mesma
 * regra de AtendimentosRelatoriosController (web) - getRespostas/
 * storeResposta/destroyResposta/destroyRespostaFoto - sem duplicar.
 */
class RelatorioFormularioController extends Controller
{
    private function relatorioComPosse(int $id, array $with = []): AtendimentoRelatorio
    {
        $relatorio = AtendimentoRelatorio::with(array_unique([...$with, 'atendimento']))->findOrFail($id);

        if (! Auth::user()->can('acessar', $relatorio->atendimento)) {
            abort(403, 'Você não tem acesso a este atendimento.');
        }

        return $relatorio;
    }

    /**
     * GET /api/fae/v1/relatorios/{id}/formulario
     *
     * Devolve as perguntas do modelo vinculado (genericas + por sessao, ja
     * com as respostas salvas) e as flags de dado legado (clima/descricao/
     * servicos/pecas/ocorrencias) - decisao da sessao 13: o app mantem os
     * dois caminhos de renderizacao (formulario dinamico + secoes fixas
     * antigas), igual ao Web, em vez de descontinuar as secoes fixas.
     */
    public function formulario(int $id): JsonResponse
    {
        $relatorio = $this->relatorioComPosse($id, [
            'configModelo.perguntas.opcoes',
            'respostas.fotos',
            'climas', 'servicos', 'pecas', 'ocorrencias', 'itensDescricao',
        ]);

        $respostasPorPergunta = $relatorio->respostas->groupBy('aten_rel_resp_pergunta_id');

        $mapaResposta = fn ($resposta) => [
            'id' => $resposta->aten_rel_resp_id,
            'valor' => $resposta->aten_rel_resp_valor,
            'fotos' => $resposta->fotos->map(fn ($f) => [
                'id' => $f->aten_rel_resp_foto_id,
                'url' => asset('midia/' . $f->aten_rel_resp_foto_path),
                'comentario' => $f->aten_rel_resp_foto_comentario,
            ])->values(),
        ];

        $mapaPergunta = function ($pergunta) use ($respostasPorPergunta, $mapaResposta) {
            $respostas = ($respostasPorPergunta->get($pergunta->cfg_perg_id) ?? collect())
                ->sortBy('aten_rel_resp_id')
                ->map($mapaResposta)
                ->values();

            return [
                'id' => $pergunta->cfg_perg_id,
                'texto' => $pergunta->cfg_perg_texto,
                'tipo' => $pergunta->cfg_perg_tipo->value,
                'permite_anexo' => (bool) $pergunta->cfg_perg_permite_anexo,
                'repetivel' => (bool) $pergunta->cfg_perg_repetivel,
                'opcoes' => $pergunta->opcoes->map(fn ($o) => [
                    'id' => $o->cfg_perg_op_id,
                    'texto' => $o->cfg_perg_op_texto,
                ])->values(),
                'respostas' => $respostas,
            ];
        };

        $grupos = $relatorio->configModelo?->perguntasAgrupadasPorSessao()
            ?? ['genericas' => collect(), 'por_sessao' => collect()];

        $genericas = $grupos['genericas']->map($mapaPergunta)->values();

        $sessoesMarcadoras = $relatorio->configModelo?->sessoes() ?? collect();
        $sessoes = $sessoesMarcadoras->map(fn ($sessao) => [
            'id' => $sessao->cfg_perg_id,
            'nome' => $sessao->cfg_perg_sessao_nome,
            'perguntas' => ($grupos['por_sessao']->get($sessao->cfg_perg_id) ?? collect())
                ->map($mapaPergunta)->values(),
        ])->values();

        return response()->json([
            'genericas' => $genericas,
            'sessoes' => $sessoes,
            'tem_clima_legado' => $relatorio->climas->isNotEmpty(),
            'tem_descricao_legado' => $relatorio->itensDescricao->isNotEmpty() || filled($relatorio->aten_rel_descricao),
            'tem_servicos_legado' => $relatorio->servicos->isNotEmpty(),
            'tem_pecas_legado' => $relatorio->pecas->isNotEmpty(),
            'tem_ocorrencias_legado' => $relatorio->ocorrencias->isNotEmpty(),
        ]);
    }

    /**
     * POST /api/fae/v1/relatorios/{id}/respostas
     */
    public function storeResposta(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'pergunta_id' => ['required', 'integer', 'exists:config_perguntas,cfg_perg_id'],
            'valor' => ['nullable', 'string'],
            'foto' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif'],
            'foto_comentario' => ['nullable', 'string', 'max:500'],
        ], [
            'foto.mimes' => 'Tipo de imagem não permitido. Formatos aceitos: JPG, JPEG, PNG, WEBP, GIF.',
        ]);

        $relatorio = $this->relatorioComPosse($id, ['configModelo.perguntas']);

        try {
            $perguntaId = (int) $request->input('pergunta_id');
            $pergunta = $relatorio->configModelo?->perguntas->firstWhere('cfg_perg_id', $perguntaId);

            if ($pergunta?->cfg_perg_repetivel) {
                $resposta = AtendimentoRelatorioResposta::create([
                    'aten_rel_resp_relatorio_id' => $id,
                    'aten_rel_resp_pergunta_id' => $perguntaId,
                    'aten_rel_resp_valor' => $request->input('valor'),
                ]);
            } else {
                $resposta = AtendimentoRelatorioResposta::firstOrNew([
                    'aten_rel_resp_relatorio_id' => $id,
                    'aten_rel_resp_pergunta_id' => $perguntaId,
                ]);
                $resposta->aten_rel_resp_valor = $request->input('valor');
                $resposta->save();
            }

            $fotoUrl = null;
            if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
                $file = $request->file('foto');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $ext = $file->getClientOriginalExtension();
                $safeName = Str::slug($originalName) . '_' . Str::random(8) . '.' . $ext;
                $path = $file->storeAs("atendimentos_relatorios/{$id}/perguntas", $safeName, 'public');
                if ($path === false) {
                    return response()->json(['message' => 'Falha ao gravar a foto em disco.'], 500);
                }
                $resposta->fotos()->create([
                    'aten_rel_resp_foto_path' => $path,
                    'aten_rel_resp_foto_comentario' => $request->input('foto_comentario'),
                ]);
                $fotoUrl = asset('midia/' . $path);
            }

            return response()->json([
                'message' => 'Resposta salva com sucesso.',
                'resposta_id' => $resposta->aten_rel_resp_id,
                'foto_url' => $fotoUrl,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao salvar resposta.'], 500);
        }
    }

    /**
     * DELETE /api/fae/v1/relatorios/{id}/respostas/{respostaId}
     */
    public function destroyResposta(int $id, int $respostaId): JsonResponse
    {
        $this->relatorioComPosse($id);

        try {
            $resposta = AtendimentoRelatorioResposta::with('fotos')
                ->where('aten_rel_resp_relatorio_id', $id)
                ->where('aten_rel_resp_id', $respostaId)
                ->firstOrFail();

            foreach ($resposta->fotos as $foto) {
                if (Storage::disk('public')->exists($foto->aten_rel_resp_foto_path)) {
                    Storage::disk('public')->delete($foto->aten_rel_resp_foto_path);
                }
            }
            $resposta->delete();

            return response()->json(['message' => 'Resposta removida!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao remover resposta.'], 500);
        }
    }

    /**
     * DELETE /api/fae/v1/relatorios/{id}/respostas-fotos/{fotoId}
     */
    public function destroyRespostaFoto(int $id, int $fotoId): JsonResponse
    {
        $this->relatorioComPosse($id);

        try {
            $foto = AtendimentoRelatorioRespostaFoto::whereHas(
                'resposta',
                fn ($q) => $q->where('aten_rel_resp_relatorio_id', $id)
            )->where('aten_rel_resp_foto_id', $fotoId)->firstOrFail();

            if (Storage::disk('public')->exists($foto->aten_rel_resp_foto_path)) {
                Storage::disk('public')->delete($foto->aten_rel_resp_foto_path);
            }
            $foto->delete();

            return response()->json(['message' => 'Foto removida!']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Erro ao remover foto.'], 500);
        }
    }
}