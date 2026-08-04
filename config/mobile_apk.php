<?php

// Configuração da versão mais recente do app móvel BuildFlow (Flutter),
// consultada por GET /api/mcl/v1/app/versao. Atualizar as variáveis de
// ambiente (não este arquivo) a cada nova release do APK.
return [

    // Versão+build mais recente disponível, no mesmo formato do pubspec.yaml
    // do app (ex: "1.0.6+23"). Comparada com PackageInfo no dispositivo.
    'versao_minima' => env('MOBILE_APK_VERSAO_MINIMA', ''),

    // Quando true, o app bloqueia o uso (só quando online e sem pendências
    // por enviar — ver regra no app) até o técnico atualizar.
    'obrigatoria' => env('MOBILE_APK_OBRIGATORIA', false),

    // URL pública (sem autenticação) para baixar o APK dessa versão.
    'url_apk' => env('MOBILE_APK_URL', ''),

];
