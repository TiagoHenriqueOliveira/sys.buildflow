<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * RF012 — lançada quando se tenta criar/editar/excluir um dia num relatório
 * que ainda está no formato antigo de horário/clima (uma linha só, sem
 * nenhum registro em atendimentos_relatorios_dias). Controllers (Mcl e web)
 * capturam e respondem 409, conforme o contrato de API.
 */
class RelatorioDiaLegadoException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Este relatório está no formato antigo de horários, somente leitura.');
    }
}
