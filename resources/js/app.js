import './bootstrap';

import 'bootstrap-icons/font/bootstrap-icons.css';
import Alpine from 'alpinejs';
import { registerSbAdmin } from './sbadmin/app';
import { registerConfirmModal } from './confirm-modal';
import { somenteDigitos, formatarCnpj, formatarTelefone } from './formatters';

registerSbAdmin(Alpine);
registerConfirmModal(Alpine);

window.Alpine = Alpine;
window.somenteDigitos = somenteDigitos;
window.formatarCnpj = formatarCnpj;
window.formatarTelefone = formatarTelefone;
Alpine.start();
