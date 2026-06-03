import './bootstrap';

import jQuery from 'jquery';

window.$ = jQuery;
window.jQuery = jQuery;

import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

import select2 from 'select2';

select2(window, $);

import Alpine from 'alpinejs';
window.Alpine = Alpine;

import 'bootstrap/dist/js/bootstrap.bundle.min.js';

import './maintenance';

Alpine.start();


