import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Alpine.js
import Alpine from 'alpinejs';
import { createSubscriptionIpdr } from './subscriptions/ipdr';

import { createSubscriptionNetworkFields } from './subscriptions/network-fields';

Alpine.data('subscriptionIpdr', createSubscriptionIpdr);
Alpine.data('subscriptionNetworkFields', createSubscriptionNetworkFields);

window.Alpine = Alpine;

// Import customer components (these will register Alpine data)
import './customers/index.js';
import './customers/create.js';
import './vpn-users/index.js';

Alpine.start();
