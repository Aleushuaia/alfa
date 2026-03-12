/**
 * SAE Kayen Dashboard — Entry point JavaScript
 */

// Bootstrap 5
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// AdminLTE 4
import { createApp } from 'admin-lte';
window.adminlte = { createApp };

// OverlayScrollbars (requerido por AdminLTE 4)
import { OverlayScrollbars } from 'overlayscrollbars';
window.OverlayScrollbars = OverlayScrollbars;

// Alpine.js
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// ApexCharts
import ApexCharts from 'apexcharts';
window.ApexCharts = ApexCharts;

// Axios
import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
