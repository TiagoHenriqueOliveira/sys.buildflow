/**
 * Autocomplete de texto simples (busca por nome, seleciona um ID) sem
 * jQuery/jQuery UI — substitui o antigo setupAutocomplete() de
 * public/js/app/utils.js (baseado no plugin jquery-ui autocomplete,
 * removido nesta migração pro sbadmin/dashboard, ver CLAUDE.md). Usado nos
 * campos "Cliente" dos modais de Atendimentos e Relatórios de Atendimento,
 * que continuam sendo endpoints de busca no backend (?term=) inalterados.
 *
 * Registrada em `window.setupAutocomplete` a partir de resources/js/app.js
 * pra poder ser chamada de <script> inline no Blade sem import por página.
 */
export function setupAutocomplete(inputSelector, hiddenInputSelector, url, options = {}) {
    const input = document.querySelector(inputSelector);
    const hidden = document.querySelector(hiddenInputSelector);
    if (!input || !hidden) {
        return;
    }

    const minLength = options.minLength ?? 3;

    const list = document.createElement('ul');
    list.className = 'sbadmin-autocomplete-list';
    list.hidden = true;

    const wrapper = input.parentElement;
    if (wrapper && getComputedStyle(wrapper).position === 'static') {
        wrapper.style.position = 'relative';
    }
    input.insertAdjacentElement('afterend', list);

    let debounceTimer = null;
    let abortController = null;

    function closeList() {
        list.hidden = true;
        list.innerHTML = '';
    }

    function renderItems(items) {
        list.innerHTML = '';

        if (!items || !items.length) {
            closeList();
            return;
        }

        items.forEach((item) => {
            const li = document.createElement('li');
            li.textContent = item.label;
            li.addEventListener('mousedown', (event) => {
                event.preventDefault();
                input.value = item.label;
                hidden.value = item.id;
                closeList();
                if (typeof options.onSelect === 'function') {
                    options.onSelect(item);
                }
            });
            list.appendChild(li);
        });

        list.hidden = false;
    }

    input.addEventListener('input', () => {
        hidden.value = '';
        const term = input.value.trim();

        clearTimeout(debounceTimer);
        abortController?.abort();

        if (term.length < minLength) {
            closeList();
            return;
        }

        debounceTimer = setTimeout(() => {
            abortController = new AbortController();

            fetch(`${url}?term=${encodeURIComponent(term)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: abortController.signal,
            })
                .then((response) => response.json())
                .then((data) => renderItems(data))
                .catch((error) => {
                    if (error.name !== 'AbortError') {
                        closeList();
                    }
                });
        }, 250);
    });

    // Delay pra permitir que o mousedown do item registre o click antes do
    // blur fechar a lista.
    input.addEventListener('blur', () => setTimeout(closeList, 150));

    document.addEventListener('click', (event) => {
        if (event.target !== input && !list.contains(event.target)) {
            closeList();
        }
    });
}
