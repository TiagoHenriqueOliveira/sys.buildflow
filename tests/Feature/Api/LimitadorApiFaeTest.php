<?php

namespace Tests\Feature\Api;

use App\Http\Middleware\TrustProxies;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Auditoria 27/11/2026, S-23: o limitador da API usa a identidade do token
 * (dois técnicos no mesmo IP não dividem o limite) e o IP real atrás de um
 * proxy confiável vem do X-Forwarded-For.
 */
class LimitadorApiFaeTest extends TestCase
{
    use RefreshDatabase;

    private function chaveDoLimite(?string $token, string $ip = '10.0.0.9'): string
    {
        $this->app['auth']->forgetGuards();
        $request = Request::create('/api/fae/v1/me', 'GET', server: ['REMOTE_ADDR' => $ip]);
        if ($token) {
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }
        $this->app->instance('request', $request);

        return RateLimiter::limiter('api')($request)->key;
    }

    public function test_dois_tecnicos_no_mesmo_ip_nao_dividem_o_limite(): void
    {
        $ana = Usuario::factory()->tecnico()->create();
        $beto = Usuario::factory()->tecnico()->create();

        $chaveAna = $this->chaveDoLimite($ana->createToken('a', ['fae-app'])->plainTextToken);
        $chaveBeto = $this->chaveDoLimite($beto->createToken('b', ['fae-app'])->plainTextToken);

        $this->assertSame('usuario:' . $ana->user_id, $chaveAna);
        $this->assertSame('usuario:' . $beto->user_id, $chaveBeto);
        $this->assertSame('ip:10.0.0.9', $this->chaveDoLimite(null));
        $this->assertSame('ip:10.0.0.9', $this->chaveDoLimite('token-invalido'));
    }

    public function test_ip_real_atras_de_proxy_confiavel(): void
    {
        $ipVisto = function (string $remoto) {
            $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => $remoto, 'HTTP_X_FORWARDED_FOR' => '200.150.10.20']);
            $ip = null;
            (new TrustProxies)->handle($request, function (Request $r) use (&$ip) {
                $ip = $r->ip();

                return response('ok');
            });

            return $ip;
        };

        config(['proxies.confiaveis' => '']);
        $this->assertSame('162.158.1.1', $ipVisto('162.158.1.1'), 'sem proxy confiável, o cabeçalho é ignorado');

        config(['proxies.confiaveis' => 'cloudflare']);
        $this->assertSame('200.150.10.20', $ipVisto('162.158.1.1'));
        $this->assertSame('8.8.8.8', $ipVisto('8.8.8.8'), 'quem não é da Cloudflare não pode forjar o IP');

        config(['proxies.confiaveis' => '10.1.1.1, 10.1.1.2']);
        $this->assertSame('200.150.10.20', $ipVisto('10.1.1.2'));
    }
}
