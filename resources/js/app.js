import './bootstrap';

import 'bootstrap-icons/font/bootstrap-icons.css';
import '@fontsource/ubuntu-sans/400.css';
import '@fontsource/ubuntu-sans/500.css';
import '@fontsource/ubuntu-sans/600.css';
import '@fontsource/ubuntu-sans/700.css';
import Alpine from 'alpinejs';
import { registerSbAdmin } from './sbadmin/app';
import { registerConfirmModal } from './confirm-modal';
import { somenteDigitos, formatarCnpj, formatarTelefone } from './formatters';
import { setupAutocomplete } from './autocomplete';

registerSbAdmin(Alpine);
registerConfirmModal(Alpine);

window.Alpine = Alpine;
window.somenteDigitos = somenteDigitos;
window.formatarCnpj = formatarCnpj;
window.formatarTelefone = formatarTelefone;
window.setupAutocomplete = setupAutocomplete;
Alpine.start();
