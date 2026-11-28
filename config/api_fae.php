<?php

// Flags da API fae/v1 (contrato CONTRATO-API-FAE-V1.md). Cada regra que o
// app publicado não acompanha fica desligada até o app novo estar
// instalado nos aparelhos — ver a seção 12 do contrato e a ordem sugerida
// no ACOMPANHAMENTO-WEB.md.
return [

    // §4 — sem "Idempotency-Key" nos POST que criam algo: 428
    // "idempotencia_obrigatoria" quando true.
    'exigir_idempotencia' => (bool) env('API_EXIGIR_IDEMPOTENCIA', false),

    // §6 — PUT de cliente/orçamento/roteiro sem "If-Match": 428
    // "versao_obrigatoria" quando true.
    'exigir_if_match' => (bool) env('API_EXIGIR_IF_MATCH', false),

];
