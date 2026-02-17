function createInvoice() {
    return {
        form: {
            customer_name: '',
            customer_id: null,
            subscription_code: '',
            invoice_number: 'INV-2024-' + String(Math.floor(Math.random() * 900) + 100),
            issue_date: new Date().toISOString().split('T')[0],
            due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
            billing_period_from: '',
            billing_period_to: '',
            items: [
                { description: '', quantity: 1, unit_price: 0, total: 0 }
            ],
            discount_percent: 0,
            tax_percent: 10,
            tax_type: 'exclusive',
            notes: '',
            subtotal: 0,
            discount_amount: 0,
            tax_amount: 0,
            total: 0
        },

        customers: [
            { id: 1, name: 'John Smith', email: 'john@example.com', code: 'CUS-001' },
            { id: 2, name: 'Sarah Johnson', email: 'sarah@example.com', code: 'CUS-002' },
            { id: 3, name: 'Tech Corp Ltd', email: 'billing@techcorp.com', code: 'CUS-003' },
            { id: 4, name: 'Mike Williams', email: 'mike@example.com', code: 'CUS-004' },
            { id: 5, name: 'Emily Davis', email: 'emily@example.com', code: 'CUS-005' },
            { id: 6, name: 'ABC Enterprises', email: 'accounts@abc.com', code: 'CUS-006' },
            { id: 7, name: 'Robert Brown', email: 'robert@example.com', code: 'CUS-007' }
        ],

        subscriptions: [
            { code: 'SUB-001', name: 'Business Fiber 100Mbps' },
            { code: 'SUB-002', name: 'Home Broadband 50Mbps' },
            { code: 'SUB-003', name: 'Enterprise Fiber 1Gbps' },
            { code: 'SUB-004', name: 'Home Broadband 25Mbps' },
            { code: 'SUB-005', name: 'Business Fiber 200Mbps' },
            { code: 'SUB-006', name: 'SOHO Broadband 100Mbps' }
        ],

        get filteredCustomers() {
            if (!this.form.customer_name) {
                return this.customers;
            }
            return this.customers.filter(c =>
                c.name.toLowerCase().includes(this.form.customer_name.toLowerCase()) ||
                c.email.toLowerCase().includes(this.form.customer_name.toLowerCase()) ||
                c.code.toLowerCase().includes(this.form.customer_name.toLowerCase())
            );
        },

        selectCustomer(customer) {
            this.form.customer_name = customer.name;
            this.form.customer_id = customer.id;
        },

        addLineItem() {
            this.form.items.push({
                description: '',
                quantity: 1,
                unit_price: 0,
                total: 0
            });
        },

        removeLineItem(index) {
            if (this.form.items.length > 1) {
                this.form.items.splice(index, 1);
            } else {
                this.form.items = [{ description: '', quantity: 1, unit_price: 0, total: 0 }];
            }
        },

        formatCurrency(value) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(value || 0);
        },

        calculateTotals() {
            // Calculate line item totals
            this.form.items.forEach(item => {
                item.total = item.quantity * item.unit_price;
            });

            // Calculate subtotal
            this.form.subtotal = this.form.items.reduce((sum, item) => sum + item.total, 0);

            // Calculate discount
            this.form.discount_amount = this.form.subtotal * (this.form.discount_percent / 100);

            // Calculate taxable amount
            const taxableAmount = this.form.subtotal - this.form.discount_amount;

            // Calculate tax
            this.form.tax_amount = taxableAmount * (this.form.tax_percent / 100);

            // Calculate total
            this.form.total = taxableAmount + this.form.tax_amount;
        },

        saveAsDraft() {
            this.calculateTotals();
            console.log('Saving as draft:', this.form);
            alert('Invoice saved as draft!');
        },

        createInvoice() {
            if (!this.form.customer_name) {
                alert('Please select a customer');
                return;
            }
            if (this.form.items.every(item => !item.description)) {
                alert('Please add at least one line item with a description');
                return;
            }

            this.calculateTotals();
            console.log('Creating invoice:', this.form);
            alert('Invoice created successfully!');
            // In a real app, redirect to the invoice show page
        }
    }
}
