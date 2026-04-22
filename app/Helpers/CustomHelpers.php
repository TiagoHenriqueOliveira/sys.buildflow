<?php

use Carbon\Carbon;

Carbon::setLocale('pt_BR');

// função para formatar data no formato 01/01/2000
if (!function_exists('getFormatDiaMesAno')) {
    function getFormatDiaMesAno($data)
    {
        if (!empty($data)) {
            return Carbon::parse($data)->format('d/m/Y');
        }

        return '';
    }
}

// função para retornar o dia da semana conforme a data (ex: "Segunda-feira")
if (!function_exists('getFormatDiaSemana')) {
    function getFormatDiaSemana($data)
    {
        if (empty($data)) {
            return '';
        }

        return ucfirst(
            Carbon::parse($data)
                ->locale('pt_BR')
                ->translatedFormat('l')
        );
    }
}

// função para retornar o mês e ano atual no formato "Fevereiro de 2025"
if (!function_exists('getFormatMesAnoExtenso')) {
    function getFormatMesAnoExtenso($data = null)
    {

        if ($data === null) {
            $data = new DateTime('first day of next month');
        } else {
            $data = new DateTime($data);
        }

        // Configura o formatador de data para português do Brasil
        $formatter = new IntlDateFormatter(
            'pt_BR',
            IntlDateFormatter::LONG,
            IntlDateFormatter::NONE
        );

        // Define o padrão correto para exibir "Março de 2025"
        $formatter->setPattern('MMMM\'/\'yyyy');

        return ucfirst($formatter->format($data));
    }
}

// função para converter data DD/MM/YYYY em YYYY-MM-DD
if (!function_exists('setConvertData')) {
    function setConvertData($data)
    {
        // Verifica se a data está no formato correto (DD/MM/YYYY)
        if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $data)) {
            return false; // Retorna false se o formato for inválido
        }

        // Converte a data para o formato YYYY-MM-DD
        $data_convertida = DateTime::createFromFormat('d/m/Y', $data);

        // Verifica se a conversão foi bem-sucedida
        return $data_convertida ? $data_convertida->format('Y-m-d') : false;
    }
}

// função para formatar valores
if (!function_exists('getFormatValor')) {
    function getFormatValor($data)
    {
        // Remove espaços extras e converte vírgula decimal para ponto
        $data = str_replace(',', '.', trim($data));

        // Verifica se é um número válido
        if (!is_numeric($data)) {
            return '0,00';
        }

        // Retorna o valor formatado com 2 casas decimais
        return number_format((float)$data, 2, ',', '.');
    }
}

// função para conversão de valores
if (!function_exists('setConvertValor')) {
    function setConvertValor($data)
    {
        // Remove espaços extras e caracteres não numéricos (exceto ponto e vírgula)
        $data = trim($data);
        $data = preg_replace('/[^\d,.-]/', '', $data);

        // Se a string estiver vazia após a limpeza, retorna 0.00
        if ($data === '' || !is_numeric(str_replace(',', '.', $data))) {
            return number_format(0, 2, ".", "");
        }

        // Substitui vírgula por ponto para padronizar o formato decimal
        $valor_convertido = str_replace(",", ".", $data);

        // Converte para número float e formata com duas casas decimais
        return number_format((float) $valor_convertido, 2, ".", "");
    }
}
