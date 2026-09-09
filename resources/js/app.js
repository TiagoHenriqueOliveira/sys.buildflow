import './bootstrap';

import 'bootstrap-icons/font/bootstrap-icons.css';
import Alpine from 'alpinejs';
import { registerSbAdmin } from './sbadmin/app';
import { registerConfirmModal } from './confirm-modal';

registerSbAdmin(Alpine);
registerConfirmModal(Alpine);

window.Alpine = Alpine;
Alpine.start();
