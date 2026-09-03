<?php

namespace App\Repositories\Contracts;

interface CrudRepositoryInterface
{
    /**
     * Retorna todos os registros.
     * Cada implementação pode aceitar parâmetros próprios de filtro
     * definindo argumentos opcionais.
     */
    public function all();

    public function create(array $data);

    public function update(int $id, array $data);
}
