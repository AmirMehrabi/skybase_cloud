function editInvoice() {
    return {
        form: {
            id: 1,
            customer_name: 'John Smith',
            customer_id: 1,
            subscription_code: 'SUB-001',
            invoice_number: 'INV-2024-001',
            issue_date: '2024-02-01',
            due_date: '2024-02-28',
            billing_period_from: '2024-02',
            billing_period_to: '2024-02',
            items: [
                { description: 'Business Fiber 100Mbps - Monthly Subscription', quantity: 1, unit_price: 100.00, total: 100.00 },
                { description: 'Additional IP Address (x5)', quantity: 5, unit_price: 2.00, total: 10.00 },
                { description: 'Installation Fee (One-time)', quantity: 1, unit_price: 3.64, total: 3.64 }
            ],
            discount_percent: 0,
            tax_percent: 10,
            tax_type: 'exclusive',
            notes: 'Monthly internet service charges for February 2024.',
            subtotal: 113.64,
            discount_amount: 0,
            tax_amount: 11.36,
            total: 125.00,
            paid_amount: 125.00,
            balance_due: 0,
            status: 'paid'
        },

        subscriptions: [
            { code: 'SUB-001', name: 'Business Fiber 100Mbps' },
            { code: 'SUB-002', name: 'Home Broadband 50Mbps' },
            { code: 'SUB-003', name: 'Enterprise Fiber 1Gbps' },
            { code: 'SUB-004', name: 'Home Broadband 25Mbps' },
            { code: 'SUB-005', name: 'Business Fiber 200Mbps' },
            { code: 'SUB-006', name: 'SOHO Broadband 100Mbps' }
        ],

        addLineItem() {
            this.form.items.push({
                description: '',
                quantity: 1,
                unit_price: 0,
                total: 0
            });
            this.calculateTotals();
        },

        removeLineItem(index) {
            if (this.form.items.length > 1) {
                this.form.items.splice(index, 1);
            } else {
                this.form.items = [{ description: '', quantity: 1, unit_price: 0, total: 0 }];
            }
            this.calculateTotals();
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

            // Recalculate balance
            this.form.balance_due = this.form.total - this.form.paid_amount;
        },

        saveAsDraft() {
            this.form.status = 'draft';
            this.calculateTotals();
            console.log('Saving as draft:', this.form);
            alert('Invoice saved as draft!');
        },

        updateInvoice() {
            if (!this.form.customer_name) {
                alert('Please select a customer');
                return;
            }
            if (this.form.items.every(item => !item.description)) {
                alert('Please add at least one line item with a description');
                return;
            }

            this.calculateTotals();
            console.log('Updating invoice:', this.form);
            alert('Invoice updated successfully!');
        }
    }
}
