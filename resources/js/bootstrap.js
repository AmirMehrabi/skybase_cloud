import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Alpine.js
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Import customer data and components (these will register Alpine data)
import './customers/data.js';
import './customers/index.js';
import './customers/show.js';

Alpine.start();
