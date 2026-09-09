/**
 * Máscaras de exibição reutilizáveis entre telas (CNPJ, telefone) — extraídas
 * do antigo public/js/app/clientes.js (jQuery + jquery-mask) na migração pra
 * sbadmin/dashboard. Sem jQuery: aplicadas via oninput simples (formata só
 * quando a quantidade de dígitos bate, sem máscara progressiva
 * caractere-a-caractere como o jquery-mask fazia) — suficiente pro caso de
 * uso (o valor é sempre normalizado nos dígitos no backend, ver
 * ClienteRequest::prepareForValidation). Registradas em `window` a partir de
 * resources/js/app.js pra poderem ser chamadas de atributos oninput inline
 * no Blade sem precisar de um <script type="module"> por página.
 */
export function somenteDigitos(valor) {
    return (valor || '').toString().replace(/\D+/g, '');
}

export function formatarCnpj(valor) {
    const digitos = somenteDigitos(valor);

    if (digitos.length !== 14) {
        return digitos;
    }

    return digitos.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
}

export function formatarTelefone(valor) {
    const digitos = somenteDigitos(valor);

    if (digitos.length === 11) {
        return digitos.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
    }

    if (digitos.length === 10) {
        return digitos.replace(/^(\d{2})(\d{4})(\d{4})$/, '($1) $2-$3');
    }

    return digitos;
}
