document.addEventListener('alpine:init', () => {
    Alpine.data('customerCreate', () => ({
        form: {
            customerType: 'individual',
            firstName: '',
            lastName: '',
            companyName: '',
            nationalId: '',
            email: '',
            phone: '',
            mobile: '',
            whatsapp: '',
            addressLine1: '',
            addressLine2: '',
            city: '',
            state: '',
            postalCode: '',
            country: 'United States',
            plan: '',
            site: '',
            router: '',
            pppoeUsername: '',
            pppoePassword: '',
            billingCycle: 'monthly',
            initialBalance: 0,
            creditLimit: 0,
            taxExempt: false,
            status: 'pending',
            autoActivate: false,
        },

        get generatedCustomerCode() {
            return 'CUS-2024-' + Math.floor(Math.random() * 10000).toString().padStart(4, '0');
        },

        save() {
            // Validate
            if (!this.validate()) {
                return;
            }

            // In real app, send to API
            alert('Customer created successfully!');
            window.location.href = '/customers';
        },

        saveDraft() {
            alert('Customer saved as draft!');
            window.location.href = '/customers';
        },

        validate() {
            if (this.form.customerType === 'individual' && !this.form.firstName) {
                alert('First name is required');
                return false;
            }
            if (this.form.customerType === 'individual' && !this.form.lastName) {
                alert('Last name is required');
                return false;
            }
            if (this.form.customerType === 'business' && !this.form.companyName) {
                alert('Company name is required');
                return false;
            }
            if (!this.form.email) {
                alert('Email is required');
                return false;
            }
            if (!this.form.mobile) {
                alert('Mobile number is required');
                return false;
            }
            if (!this.form.plan) {
                alert('Please select a plan');
                return false;
            }
            if (!this.form.site) {
                alert('Please select a site');
                return false;
            }
            if (!this.form.router) {
                alert('Please select a router');
                return false;
            }
            return true;
        },
    }));
});
