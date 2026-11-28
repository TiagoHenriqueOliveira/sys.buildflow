<?php

namespace App\Services;

use App\Models\CodigoAcesso;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Auditoria 27/11/2026 (S-08) — códigos de uso único gerados pelo
 * administrador (decisões do usuário de 28/11/2026):
 *
 * - ATIVACAO_MFA (72 h): conta nova ou MFA redefinido só cadastra o
 *   autenticador com este código — ter a senha não basta mais;
 * - REDEFINIR_SENHA (24 h): "Esqueci minha senha" na tela de login, junto
 *   com o código do autenticador. O administrador nunca fica sabendo a
 *   senha nova.
 *
 * O código tem 10 caracteres sem os ambíguos (0/O, 1/I), exibido como
 * XXXXX-XXXXX uma única vez; o banco guarda só o HMAC-SHA256 dele (chave
 * da aplicação). Gerar um código novo revoga o anterior do mesmo tipo.
 * 5 códigos errados bloqueiam aquele tipo de código do usuário por 15 min.
 */
class CodigoAcessoService
{
    public const ATIVACAO_MFA = 'ativacao_mfa';

    public const REDEFINIR_SENHA = 'redefinir_senha';

    private const VALIDADE_HORAS = [self::ATIVACAO_MFA => 72, self::REDEFINIR_SENHA => 24];

    private const ALFABETO = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public const MAXIMO_ERROS = 5;

    public const BLOQUEIO_SEGUNDOS = 900;

    /** @return array{codigo: string, expira_em: Carbon} */
    public function gerar(Usuario $alvo, string $tipo, ?Usuario $autor): array
    {
        $this->revogar($alvo, $tipo);

        $codigo = '';
        for ($i = 0; $i < 10; $i++) {
            $codigo .= self::ALFABETO[random_int(0, strlen(self::ALFABETO) - 1)];
        }
        $expiraEm = now()->addHours(self::VALIDADE_HORAS[$tipo]);

        CodigoAcesso::create([
            'cod_usuario_id' => $alvo->user_id,
            'cod_tipo' => $tipo,
            'cod_hash' => $this->hash($codigo),
            'cod_expira_em' => $expiraEm,
            'cod_criado_por' => $autor?->user_id,
        ]);
        RateLimiter::clear($this->chaveErros($alvo, $tipo));

        AuditService::log('autenticacao', 'codigo_gerado', $alvo->user_id, [], [
            'tipo' => $tipo, 'expira_em' => $expiraEm->toDateTimeString(), 'gerado_por' => $autor?->user_id,
        ]);

        return ['codigo' => substr($codigo, 0, 5) . '-' . substr($codigo, 5), 'expira_em' => $expiraEm];
    }

    /** Revoga os códigos ainda não usados daquele tipo (ex.: MFA já cadastrado por outro caminho). */
    public function revogar(Usuario $alvo, string $tipo): void
    {
        CodigoAcesso::where('cod_usuario_id', $alvo->user_id)
            ->where('cod_tipo', $tipo)
            ->whereNull('cod_usado_em')
            ->whereNull('cod_revogado_em')
            ->update(['cod_revogado_em' => now()]);
    }

    /**
     * Confere o código sem gastá-lo (o chamador gasta com consumir() só
     * depois de validar o resto, ex.: o código do autenticador). Código
     * errado, vencido, usado ou revogado conta como erro.
     */
    public function conferir(Usuario $alvo, string $tipo, string $codigo): ?CodigoAcesso
    {
        if ($this->bloqueado($alvo, $tipo)) {
            return null;
        }

        $normalizado = preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($codigo));
        $registro = strlen((string) $normalizado) === 10
            ? CodigoAcesso::where('cod_usuario_id', $alvo->user_id)
                ->where('cod_tipo', $tipo)
                ->whereNull('cod_usado_em')
                ->whereNull('cod_revogado_em')
                ->where('cod_expira_em', '>', now())
                ->latest('cod_id')
                ->first()
            : null;

        if (! $registro || ! hash_equals($registro->cod_hash, $this->hash($normalizado))) {
            RateLimiter::hit($this->chaveErros($alvo, $tipo), self::BLOQUEIO_SEGUNDOS);

            return null;
        }

        return $registro;
    }

    public function consumir(CodigoAcesso $registro): void
    {
        $registro->forceFill(['cod_usado_em' => now()])->save();
        RateLimiter::clear($this->chaveErros($registro->usuario, $registro->cod_tipo));
    }

    public function bloqueado(Usuario $alvo, string $tipo): bool
    {
        return RateLimiter::tooManyAttempts($this->chaveErros($alvo, $tipo), self::MAXIMO_ERROS);
    }

    public function minutosParaLiberar(Usuario $alvo, string $tipo): int
    {
        return max(1, (int) ceil(RateLimiter::availableIn($this->chaveErros($alvo, $tipo)) / 60));
    }

    /** Há código válido (para a tela de usuários mostrar "código pendente"). */
    public function pendente(Usuario $alvo, string $tipo): ?CodigoAcesso
    {
        return CodigoAcesso::where('cod_usuario_id', $alvo->user_id)
            ->where('cod_tipo', $tipo)
            ->whereNull('cod_usado_em')
            ->whereNull('cod_revogado_em')
            ->where('cod_expira_em', '>', now())
            ->latest('cod_id')
            ->first();
    }

    private function hash(string $codigo): string
    {
        return hash_hmac('sha256', $codigo, (string) config('app.key'));
    }

    private function chaveErros(Usuario $alvo, string $tipo): string
    {
        return "codigo_acesso.erros.{$tipo}.{$alvo->user_id}";
    }
}
