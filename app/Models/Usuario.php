<?php

namespace App\Models;

use App\Enums\NivelAcesso;
use App\Support\Permissoes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'user_id';
    public $timestamps = false;

    protected $fillable = [
        'user_nivel_acesso',
        'user_nome',
        'user_email',
        'user_senha',
        'user_ativo',
        'user_protegido',
    ];

    protected $hidden = [
        'user_senha',
    ];

    protected $casts = [
        'user_ativo'       => 'boolean',
        'user_protegido'   => 'boolean',
        'user_nivel_acesso' => 'integer',
    ];

    public function isProtegido(): bool
    {
        return (bool) $this->user_protegido;
    }

    /**
     * Acesso total, sem depender do cadastro "Perfis de Acesso" — inclusive
     * a funções criadas depois (pedido do cliente 2026-11-16): o perfil
     * Administrador e o usuário protegido (3iodigital26@gmail.com, mesmo
     * que alguém altere o nível dele direto no banco).
     */
    public function temAcessoTotal(): bool
    {
        return $this->isProtegido()
            || (int) $this->user_nivel_acesso === NivelAcesso::Administrador->value;
    }

    /**
     * Pedido do cliente (2026-11-27): o PERFIL Vendedor só enxerga o que é
     * dele — clientes em que é o vendedor responsável (ou sem vendedor),
     * orçamentos em que é o vendedor (responsável ou adicional, CRM04) e os
     * próprios roteiros de viagem. Vale só para o perfil Vendedor; os demais
     * perfis (inclusive Comercial) continuam vendo tudo. Ver os escopos
     * visivelPara() de Cliente, Orcamento e RoteiroViagem.
     */
    public function veSoOsProprios(): bool
    {
        return ! $this->temAcessoTotal()
            && (int) $this->user_nivel_acesso === NivelAcesso::Vendedor->value;
    }

    /** Permissão de tela ("orcamentos.ver") — ver App\Support\Permissoes. */
    public function temPermissao(string $chave): bool
    {
        if ($this->temAcessoTotal()) {
            return true;
        }

        return in_array($chave, PerfilPermissao::chavesDoNivel((int) $this->user_nivel_acesso), true);
    }

    /** Chaves de tela concedidas (o app usa pra esconder o que não acessa). */
    public function permissoes(): array
    {
        if ($this->temAcessoTotal()) {
            return Permissoes::chavesDeTela();
        }

        return array_values(array_intersect(
            Permissoes::chavesDeTela(),
            PerfilPermissao::chavesDoNivel((int) $this->user_nivel_acesso)
        ));
    }

    /**
     * Usuários ativos de perfil marcado "Atua como vendedor" no cadastro de
     * perfis. Sem o bypass de Administrador de propósito: vendedor é papel,
     * não acesso — senão todo administrador entraria nos combos.
     */
    public function scopeVendedores(Builder $query): Builder
    {
        return $query->where('user_ativo', true)
            ->whereIn('user_nivel_acesso', PerfilPermissao::niveisCom(Permissoes::VENDEDOR));
    }

    /**
     * Usuários ativos que têm a permissão de tela — Administradores e o
     * usuário protegido sempre entram (acesso total).
     */
    /**
     * Combo "Vendedor responsável" (cliente, orçamento, roteiro): o perfil
     * Vendedor (2026-11-27) só pode atribuir a si mesmo — se escolhesse
     * outro, o registro sumiria da tela dele na hora (ver veSoOsProprios).
     */
    public function scopeVendedoresAtribuiveisPor(Builder $query, Usuario $usuario): Builder
    {
        $query->vendedores();

        return $usuario->veSoOsProprios()
            ? $query->where('user_id', $usuario->user_id)
            : $query;
    }

    public function scopeComPermissao(Builder $query, string $chave): Builder
    {
        return $query->where('user_ativo', true)->where(function (Builder $q) use ($chave) {
            $q->where('user_nivel_acesso', NivelAcesso::Administrador->value)
                ->orWhere('user_protegido', true)
                ->orWhereIn('user_nivel_acesso', PerfilPermissao::niveisCom($chave));
        });
    }

    public function getAuthPasswordName(): string
    {
        return 'user_senha';
    }
}
