<?php

namespace App\Http\Requests\Concerns;

use DateTime;
use Illuminate\Validation\Validator;

/**
 * Regras de ordem entre os horários de um dia (entrada <= início do
 * intervalo <= fim do intervalo <= saída, e entrada <= saída), usada por
 * App\Http\Requests\Mcl\UpsertDiaRequest (RF012 — API do app e aba web).
 * Só compara pares onde os dois valores estão presentes — preenchimento
 * parcial é permitido.
 *
 * Os callbacks `after` do Validator rodam mesmo quando as regras já
 * falharam: valor fora de "H:i" é ignorado aqui (a regra date_format já
 * reportou o erro) em vez de ser passado ao parser — antes isso lançava
 * exceção e virava 500 em vez de 422.
 */
trait ValidaOrdemHorarios
{
    protected function validarOrdemHorarios(
        Validator $validator,
        mixed $entrada,
        mixed $inicioIntervalo,
        mixed $fimIntervalo,
        mixed $saida,
        string $campoEntrada = 'entrada',
        string $campoInicioIntervalo = 'inicio_intervalo',
        string $campoFimIntervalo = 'fim_intervalo',
        string $campoSaida = 'saida',
    ): void {
        if (filled($inicioIntervalo) && blank($fimIntervalo)) {
            $validator->errors()->add($campoFimIntervalo, 'Informe o fim do intervalo.');

            return;
        }

        if (filled($fimIntervalo) && blank($inicioIntervalo)) {
            $validator->errors()->add($campoInicioIntervalo, 'Informe o início do intervalo.');

            return;
        }

        $tEntrada = $this->horaValida($entrada);
        $tIniInt = $this->horaValida($inicioIntervalo);
        $tFimInt = $this->horaValida($fimIntervalo);
        $tSaida = $this->horaValida($saida);

        if ($tEntrada && $tSaida && $tEntrada > $tSaida) {
            $validator->errors()->add($campoSaida, 'A saída deve ser maior ou igual à entrada.');

            return;
        }

        if ($tIniInt && $tFimInt && $tIniInt > $tFimInt) {
            $validator->errors()->add($campoFimIntervalo, 'O fim do intervalo deve ser maior ou igual ao início.');

            return;
        }

        if ($tEntrada && $tIniInt && $tIniInt < $tEntrada) {
            $validator->errors()->add($campoInicioIntervalo, 'O início do intervalo não pode ser antes da entrada.');

            return;
        }

        if ($tSaida && $tFimInt && $tFimInt > $tSaida) {
            $validator->errors()->add($campoFimIntervalo, 'O fim do intervalo não pode ser após a saída.');

            return;
        }
    }

    private function horaValida(mixed $valor): ?DateTime
    {
        if (! is_string($valor)) {
            return null;
        }

        $hora = DateTime::createFromFormat('!H:i', $valor);

        return $hora && $hora->format('H:i') === $valor ? $hora : null;
    }
}
