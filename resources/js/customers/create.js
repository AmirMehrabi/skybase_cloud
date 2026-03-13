document.addEventListener('alpine:init', () => {
    Alpine.data('customerCreate', () => ({
        form: {
            customer_type: 'individual',
            first_name: '',
            last_name: '',
            company_name: '',
            national_id: '',
            email: '',
            phone: '',
            mobile: '',
            whatsapp: '',
            address_line1: '',
            address_line2: '',
            city: '',
            state: '',
            postal_code: '',
            country: 'United States',
            plan: '',
            site: '',
            router: '',
            ip_address: '',
            pppoe_username: '',
            pppoe_password: '',
            billing_type: 'prepaid',
            billing_cycle: 'monthly',
            balance: 0,
            credit_limit: 0,
            tax_exempt: false,
            status: 'pending',
            auto_activate: false,
        },

        errors: {},
        saving: false,

        get generatedCustomerCode() {
            const now = new Date();
            const year = now.getFullYear().toString().slice(-2);
            const month = (now.getMonth() + 1).toString().padStart(2, '0');
            const day = now.getDate().toString().padStart(2, '0');
            const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0').toUpperCase();
            return `CUS-${year}${month}${day}-${random}`;
        },

        async save() {
            if (!this.validate()) {
                return;
            }

            this.saving = true;
            this.errors = {};

            try {
                const response = await fetch('/customers', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    },
                    body: JSON.stringify(this.form),
                });

                const data = await response.json();

                if (response.ok) {
                    window.location.href = '/customers';
                } else if (response.status === 422 && data.errors) {
                    // Validation errors
                    for (const [field, messages] of Object.entries(data.errors)) {
                        this.errors[field] = Array.isArray(messages) ? messages[0] : messages;
                    }
                } else {
                    alert('Error creating customer: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error creating customer:', error);
                alert('Error creating customer. Please try again.');
            } finally {
                this.saving = false;
            }
        },

        async saveDraft() {
            this.form.status = 'pending';
            await this.save();
        },

        validate() {
            this.errors = {};

            if (this.form.customer_type === 'individual' && !this.form.first_name) {
                this.errors.first_name = 'First name is required';
            }
            if (this.form.customer_type === 'individual' && !this.form.last_name) {
                this.errors.last_name = 'Last name is required';
            }
            if (this.form.customer_type === 'business' && !this.form.company_name) {
                this.errors.company_name = 'Company name is required';
            }
            if (!this.form.email) {
                this.errors.email = 'Email is required';
            }
            if (!this.form.mobile) {
                this.errors.mobile = 'Mobile number is required';
            }
            if (!this.form.address_line1) {
                this.errors.address_line1 = 'Address is required';
            }
            if (!this.form.city) {
                this.errors.city = 'City is required';
            }
            if (!this.form.plan) {
                this.errors.plan = 'Please select a plan';
            }
            if (!this.form.site) {
                this.errors.site = 'Please select a site';
            }
            if (!this.form.router) {
                this.errors.router = 'Please select a router';
            }

            return Object.keys(this.errors).length === 0;
        },

        getError(field) {
            return this.errors[field] || '';
        },

        clearError(field) {
            delete this.errors[field];
        },
    }));
});
