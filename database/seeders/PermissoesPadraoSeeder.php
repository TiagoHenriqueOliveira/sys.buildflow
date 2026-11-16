<?php

namespace Database\Seeders;

use App\Enums\NivelAcesso;
use App\Models\PerfilPermissao;
use Illuminate\Database\Seeder;

/**
 * Reproduz o acesso que cada perfil tinha antes do cadastro "Perfis de
 * Acesso" (2026-11-16), quando as regras eram fixas no código. Usado pela
 * suíte de testes (Tests\TestCase) e disponível para produção, caso se
 * prefira partir do comportamento antigo em vez dos perfis em branco:
 *
 *   php artisan db:seed --class=PermissoesPadraoSeeder
 *
 * SUBSTITUI as permissões atuais dos quatro perfis.
 */
class PermissoesPadraoSeeder extends Seeder
{
    public function run(): void
    {
        $atendimento = ['atendimentos.ver', 'relatorios.ver', 'relatorios.editar', 'mapa_demandas.ver'];
        $clientes = ['clientes.ver', 'clientes.editar'];
        $crm = [
            'orcamentos.ver', 'orcamentos.editar',
            'roteiros.ver', 'roteiros.editar',
            'mapa_relacoes.ver', 'indicadores.ver',
        ];

        $padrao = [
            NivelAcesso::Tecnico->value     => $atendimento,
            NivelAcesso::Comercial->value   => [...$atendimento, ...$clientes, ...$crm, 'vendedor'],
            NivelAcesso::Assistencia->value => [...$atendimento, ...$clientes],
            NivelAcesso::Vendedor->value    => [...$atendimento, ...$clientes],
        ];

        foreach ($padrao as $nivel => $chaves) {
            PerfilPermissao::sincronizar($nivel, $chaves);
        }
    }
}
