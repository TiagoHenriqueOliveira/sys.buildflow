<?php

namespace App\Models;

use App\Support\Permissoes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Permissão concedida a um perfil (nível de acesso). A presença da linha
 * é a concessão. Administrador não tem linhas — passa em tudo
 * (Usuario::temAcessoTotal). Ver App\Support\Permissoes.
 */
class PerfilPermissao extends Model
{
    protected $table = 'perfis_permissoes';
    protected $primaryKey = 'perf_perm_id';
    public $timestamps = false;

    protected $fillable = [
        'perf_perm_nivel',
        'perf_perm_chave',
    ];

    protected $casts = [
        'perf_perm_nivel' => 'integer',
    ];

    /** Memo por request: nível => chaves. */
    private static array $cache = [];

    public static function chavesDoNivel(int $nivel): array
    {
        return self::$cache[$nivel] ??= self::where('perf_perm_nivel', $nivel)
            ->pluck('perf_perm_chave')
            ->all();
    }

    /** Todas as concessões, agrupadas por nível (tela de perfis). */
    public static function porNivel(): array
    {
        return self::query()
            ->get(['perf_perm_nivel', 'perf_perm_chave'])
            ->groupBy('perf_perm_nivel')
            ->map(fn ($linhas) => $linhas->pluck('perf_perm_chave')->all())
            ->all();
    }

    /** Níveis que têm a chave (ex.: quem "Atua como vendedor"). */
    public static function niveisCom(string $chave): array
    {
        return self::where('perf_perm_chave', $chave)
            ->pluck('perf_perm_nivel')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    /** Substitui as permissões do nível ("editar" implica "ver"). */
    public static function sincronizar(int $nivel, array $chaves): void
    {
        $chaves = Permissoes::normalizar($chaves);

        DB::transaction(function () use ($nivel, $chaves) {
            self::where('perf_perm_nivel', $nivel)->delete();

            foreach ($chaves as $chave) {
                self::create(['perf_perm_nivel' => $nivel, 'perf_perm_chave' => $chave]);
            }
        });

        self::limparCache();
    }

    public static function limparCache(): void
    {
        self::$cache = [];
    }
}
