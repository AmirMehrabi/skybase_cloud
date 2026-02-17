import { customers } from './data.js';

document.addEventListener('alpine:init', () => {
    Alpine.data('customerEdit', (customerId) => ({
        customerId: customerId,
        customer: null,
        form: {
            customerType: 'individual',
            firstName: '',
            lastName: '',
            companyName: '',
            email: '',
            phone: '',
            mobile: '',
            whatsapp: '',
            plan: '',
            site: '',
            router: '',
            pppoeUsername: '',
            pppoePassword: '',
            balance: 0,
            creditLimit: 0,
            status: 'pending',
        },

        init() {
            // Load customer data
            this.customer = customers.find(c => c.id === parseInt(customerId));
            if (this.customer) {
                this.loadFormData();
            }
        },

        loadFormData() {
            this.form = {
                customerType: this.customer.type || 'individual',
                firstName: this.customer.first_name || '',
                lastName: this.customer.last_name || '',
                companyName: this.customer.company || '',
                email: this.customer.email || '',
                phone: this.customer.phone || '',
                mobile: this.customer.mobile || '',
                whatsapp: this.customer.whatsapp || '',
                plan: this.customer.plan || '',
                site: this.customer.site || '',
                router: this.customer.router || '',
                pppoeUsername: this.customer.pppoe_username || '',
                pppoePassword: this.customer.pppoe_password || '',
                balance: this.customer.balance || 0,
                creditLimit: this.customer.credit_limit || 0,
                status: this.customer.status || 'pending',
            };
        },

        update() {
            // Validate
            if (!this.form.email) {
                alert('Email is required');
                return;
            }
            if (!this.form.mobile) {
                alert('Mobile is required');
                return;
            }

            // In real app, send to API
            alert('Customer updated successfully!');
            window.location.href = '/customers/' + this.customerId;
        },

        suspendService() {
            this.form.status = 'suspended';
            alert('Service suspended successfully!');
        },

        resetPPoE() {
            this.form.pppoePassword = Math.random().toString(36).substring(2, 14);
            alert('PPPoE password has been reset!');
        },

        getStatusBadgeClass(status) {
            const classes = {
                active: 'bg-green-100 text-green-800 border-green-200',
                pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
                suspended: 'bg-red-100 text-red-800 border-red-200',
                terminated: 'bg-gray-100 text-gray-800 border-gray-200',
            };
            return classes[status] || classes.terminated;
        },
    }));
});
