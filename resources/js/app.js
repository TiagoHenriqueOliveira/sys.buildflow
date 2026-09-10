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
import { setupModalSubmitFeedback, iniciarFeedbackSalvamento, pararFeedbackSalvamento } from './modal-submit';

registerSbAdmin(Alpine);
registerConfirmModal(Alpine);
setupModalSubmitFeedback();

window.Alpine = Alpine;
window.somenteDigitos = somenteDigitos;
window.formatarCnpj = formatarCnpj;
window.formatarTelefone = formatarTelefone;
window.setupAutocomplete = setupAutocomplete;
// Expostos para o modal de Atendimentos, que continua submetendo via
// fetch()/JSON (ver resources/js/modal-submit.js e
// atendimentos/index.blade.php) em vez do <form method="POST"> tradicional
// que setupModalSubmitFeedback() já cobre sozinho.
window.iniciarFeedbackSalvamento = iniciarFeedbackSalvamento;
window.pararFeedbackSalvamento = pararFeedbackSalvamento;
Alpine.start();
