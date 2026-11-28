<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Contrato fae/v1 §6 (auditoria S-14): no PUT da API, campo ausente do corpo
 * fica como está gravado (antes, campo ausente virava nulo). As listas só
 * são tocadas se a chave vier no corpo. No web nada muda: os formulários
 * mandam tudo e lista ausente continua significando "lista vazia".
 */
trait PutParcialDaApi
{
    protected function ehPutParcialDaApi(): bool
    {
        return $this->isMethod('PUT') && $this->is('api/*');
    }

    /** Completa os campos simples ausentes com o valor gravado. */
    protected function completarComAtual(?Model $atual, array $campos): void
    {
        if (! $atual || ! $this->ehPutParcialDaApi()) {
            return;
        }

        $faltando = [];
        foreach ($campos as $campo) {
            if (! $this->exists($campo)) {
                $faltando[$campo] = $atual->getRawOriginal($campo);
            }
        }

        if ($faltando !== []) {
            $this->merge($faltando);
        }
    }

    /** No web (e no POST), lista ausente continua significando lista vazia. */
    protected function listasAusentesComoVazias(array $listas): void
    {
        if ($this->ehPutParcialDaApi()) {
            return;
        }

        $vazias = [];
        foreach ($listas as $lista) {
            if (! $this->exists($lista)) {
                $vazias[$lista] = [];
            }
        }

        if ($vazias !== []) {
            $this->merge($vazias);
        }
    }
}
