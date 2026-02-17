import { customers } from './data.js';

document.addEventListener('alpine:init', () => {
    Alpine.data('customerShow', (customerId) => ({
        customer: null,
        activeTab: 'overview',
        tabs: {
            overview: 'Overview',
            services: 'Services',
            invoices: 'Invoices',
            usage: 'Usage',
            tickets: 'Tickets',
            activity: 'Activity Log',
        },

        init() {
            // Load customer data
            this.customer = customers.find(c => c.id === parseInt(customerId));
            this.loadInvoices();
            this.loadActivityLog();
        },

        get invoices() {
            return [
                { id: 1, number: 'INV-2024-0156', amount: 49.99, due_date: '2024-03-15', status: 'paid' },
                { id: 2, number: 'INV-2024-0187', amount: 49.99, due_date: '2024-02-15', status: 'paid' },
                { id: 3, number: 'INV-2024-0201', amount: 49.99, due_date: '2024-01-15', status: 'paid' },
            ];
        },

        get activityLog() {
            return [
                { action: 'Payment received', description: '$49.99 payment via credit card', time: '2 hours ago' },
                { action: 'Service active', description: 'Customer service is active', time: '2 days ago' },
                { action: 'Plan changed', description: 'Upgraded from Fiber 50 to Fiber 100', time: '1 week ago' },
                { action: 'Invoice sent', description: 'Invoice INV-2024-0156 sent via email', time: '2 weeks ago' },
                { action: 'Account created', description: 'Customer account was created', time: '2 months ago' },
            ];
        },

        loadInvoices() {
            // In real app, fetch from API
        },

        loadActivityLog() {
            // In real app, fetch from API
        },

        formatBalance(amount) {
            return '$' + amount.toFixed(2);
        },

        getStatusBadgeClass(status) {
            const classes = {
                active: 'bg-green-100 text-green-800 border-green-200',
                pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
                suspended: 'bg-red-100 text-red-800 border-red-200',
                terminated: 'bg-gray-100 text-gray-800 border-gray-200',
                paid: 'bg-green-100 text-green-800 border-green-200',
            };
            return classes[status] || classes.terminated;
        },
    }));
});
