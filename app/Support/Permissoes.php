<?php

namespace App\Support;

/**
 * Catálogo de permissões por tela — fonte única pra tela "Perfis de
 * Acesso", menu, rotas (middleware `can:`) e API (/me).
 *
 * Pedido do cliente (2026-11-16): os perfis fixos tiveram pouca aderência;
 * os perfis continuam os mesmos (Técnico, Comercial, Assistência, Vendedor),
 * mas os acessos de cada um passam a ser configurados no cadastro. Cada tela
 * tem "ver" (leitura) e, quando grava algo, "editar" — chave final
 * "<tela>.<acao>", ex.: "orcamentos.editar".
 *
 * Função nova = uma entrada aqui (+ `can:` na rota). Administrador e o
 * usuário protegido passam a tê-la automaticamente
 * (Usuario::temAcessoTotal), sem precisar editar perfil nenhum.
 */
final class Permissoes
{
    public const VER = 'ver';
    public const EDITAR = 'editar';

    /** Papel, não acesso a tela: quem aparece nos combos de vendedor. */
    public const VENDEDOR = 'vendedor';

    /**
     * Grupo => [chave da tela => [rótulo, ações]].
     */
    public static function telas(): array
    {
        $verEditar = [self::VER, self::EDITAR];
        $ver = [self::VER];

        return [
            'Atendimento' => [
                'atendimentos'  => ['Atendimentos', $verEditar],
                'relatorios'    => ['Relatórios de atendimento', $verEditar],
                'mapa_demandas' => ['Mapa de demandas', $ver],
            ],
            'CRM Comercial' => [
                'clientes'      => ['Clientes', $verEditar],
                'orcamentos'    => ['Orçamentos', $verEditar],
                'roteiros'      => ['Roteiro de viagem', $verEditar],
                'mapa_relacoes' => ['Mapa de relações', $ver],
                'indicadores'   => ['Indicadores comerciais', $ver],
            ],
            'Configurações' => [
                'tipos_orcamento' => ['Tipos de orçamento', $verEditar],
                'configurador'    => ['Configurador de relatórios (perguntas e modelos)', $verEditar],
                'naturezas'       => ['Naturezas de atendimentos', $verEditar],
                'classificacoes'  => ['Classificações de cliente', $verEditar],
                'segmentos'       => ['Segmentos', $verEditar],
                'ocorrencias'     => ['Ocorrências', $verEditar],
                'usuarios'        => ['Usuários', $verEditar],
            ],
        ];
    }

    /** Todas as chaves de acesso a tela ("orcamentos.ver", ...). */
    public static function chavesDeTela(): array
    {
        $chaves = [];
        foreach (self::telas() as $telas) {
            foreach ($telas as $tela => [, $acoes]) {
                foreach ($acoes as $acao) {
                    $chaves[] = "{$tela}.{$acao}";
                }
            }
        }

        return $chaves;
    }

    /** Todas as chaves válidas (telas + papel de vendedor). */
    public static function todas(): array
    {
        return [...self::chavesDeTela(), self::VENDEDOR];
    }

    public static function existe(string $chave): bool
    {
        return in_array($chave, self::todas(), true);
    }

    /**
     * Normaliza uma lista vinda do formulário: descarta chave desconhecida
     * e garante que "editar" implica "ver".
     */
    public static function normalizar(array $chaves): array
    {
        $validas = array_values(array_intersect(array_unique($chaves), self::todas()));

        foreach ($validas as $chave) {
            if (str_ends_with($chave, '.' . self::EDITAR)) {
                $validas[] = substr($chave, 0, -strlen(self::EDITAR)) . self::VER;
            }
        }

        $validas = array_values(array_unique($validas));
        sort($validas);

        return $validas;
    }
}
