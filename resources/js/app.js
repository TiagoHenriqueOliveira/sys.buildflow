import './bootstrap';

import 'bootstrap-icons/font/bootstrap-icons.css';
import Alpine from 'alpinejs';
import { registerSbAdmin } from './sbadmin/app';

registerSbAdmin(Alpine);

window.Alpine = Alpine;
Alpine.start();
