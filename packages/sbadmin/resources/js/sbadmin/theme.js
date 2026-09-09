const STORAGE_KEY = 'sbadmin-theme';

export function getStoredTheme() {
    return localStorage.getItem(STORAGE_KEY);
}

export function getPreferredTheme() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

export function currentTheme() {
    return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
}

export function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    document.documentElement.setAttribute('data-bs-theme', theme);
    localStorage.setItem(STORAGE_KEY, theme);
}

export function initTheme() {
    // A tag <html> ja recebe data-theme/data-bs-theme via script inline no <head>
    // do layout (evita flash de tema errado); aqui apenas garantimos consistencia.
    if (!document.documentElement.hasAttribute('data-theme')) {
        applyTheme(getStoredTheme() || getPreferredTheme());
    } else if (!document.documentElement.hasAttribute('data-bs-theme')) {
        document.documentElement.setAttribute('data-bs-theme', currentTheme());
    }

    return currentTheme() === 'dark';
}
