<?php

namespace App\Models\Concerns;

/**
 * Contrato fae/v1 §6 (auditoria S-14): número de versão que sobe a cada
 * gravação do registro (web ou API), para o app detectar que outra pessoa
 * alterou o mesmo cadastro (If-Match → 409 "conflito_versao"). O model
 * define a coluna em COLUNA_VERSAO.
 */
trait TemVersao
{
    public static function bootTemVersao(): void
    {
        static::saving(function (self $model) {
            if ($model->exists) {
                $model->{static::COLUNA_VERSAO} = (int) $model->getOriginal(static::COLUNA_VERSAO) + 1;
            }
        });
    }

    public function versao(): int
    {
        return (int) $this->{static::COLUNA_VERSAO};
    }
}
