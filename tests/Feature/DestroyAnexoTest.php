<?php

namespace Tests\Feature;

use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\AtendimentoRelatorioFoto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Item 2.1 do plano de correções: destroyAnexo (API legada e API Mcl) removia
// só o registro do banco, nunca o arquivo físico — arquivo órfão ficava pra
// sempre em storage/app/public. Testa as duas variantes de API, já que o bug
// foi replicado nas duas.
class DestroyAnexoTest extends TestCase
{
    use RefreshDatabase;

    private function criarFotoComArquivo(Usuario $tecnico): array
    {
        Storage::fake('public');

        $relatorio = AtendimentoRelatorio::factory()
            ->for(Atendimento::factory()->create(['aten_usuario_id' => $tecnico->user_id]), 'atendimento')
            ->create();

        $path = UploadedFile::fake()->image('foto.jpg')->store(
            "atendimentos_relatorios/{$relatorio->aten_rel_id}/fotos",
            'public',
        );

        $foto = AtendimentoRelatorioFoto::create([
            'aten_rel_foto_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_foto_path'         => $path,
        ]);

        return [$relatorio, $foto, $path];
    }

    public function test_destroy_anexo_api_legada_apaga_arquivo_fisico(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        [$relatorio, $foto, $path] = $this->criarFotoComArquivo($tecnico);
        $token = $tecnico->createToken('test')->plainTextToken;

        Storage::disk('public')->assertExists($path);

        $response = $this->withToken($token)
            ->deleteJson("/api/v1/relatorios/{$relatorio->aten_rel_id}/anexos/foto/{$foto->aten_rel_foto_id}");

        $response->assertOk();
        $this->assertDatabaseMissing('atendimentos_relatorios_fotos', [
            'aten_rel_foto_id' => $foto->aten_rel_foto_id,
        ]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_destroy_anexo_api_mcl_apaga_arquivo_fisico(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        [$relatorio, $foto, $path] = $this->criarFotoComArquivo($tecnico);
        $token = $tecnico->createToken('test')->plainTextToken;

        Storage::disk('public')->assertExists($path);

        $response = $this->withToken($token)
            ->deleteJson("/api/mcl/v1/relatorios/{$relatorio->aten_rel_id}/anexos/foto/{$foto->aten_rel_foto_id}");

        $response->assertOk();
        $this->assertDatabaseMissing('atendimentos_relatorios_fotos', [
            'aten_rel_foto_id' => $foto->aten_rel_foto_id,
        ]);
        Storage::disk('public')->assertMissing($path);
    }
}
