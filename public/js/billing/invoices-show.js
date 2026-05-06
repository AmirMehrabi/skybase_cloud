function invoiceShow() {
    return {
        invoice: window.billingInvoiceShow || {
            id: 1,
            invoice_number: 'INV-2024-001',
            customer_name: 'John Smith',
            subscription_code: 'SUB-001',
            issue_date: '2024-02-01',
            due_date: '2024-02-28',
            billing_period: 'February 2024',
            subtotal: 113.64,
            tax: 11.36,
            discount: 0,
            total: 125.00,
            paid_amount: 125.00,
            balance_due: 0,
            status: 'paid',
            days_overdue: 0,
            notes: 'Monthly internet service charges for February 2024.',
            items: [
                { description: 'Business Fiber 100Mbps - Monthly Subscription', quantity: 1, unit_price: 100.00, total: 100.00 },
                { description: 'Additional IP Address (x5)', quantity: 5, unit_price: 2.00, total: 10.00 },
                { description: 'Installation Fee (One-time)', quantity: 1, unit_price: 3.64, total: 3.64 }
            ]
        },

        payments: (window.billingInvoiceShow && window.billingInvoiceShow.payments) || [
            { id: 1, payment_reference: 'PAY-2024-001', date: '2024-02-15', method: 'card', amount: 125.00, status: 'completed' }
        ],

        activities: [
            {
                title: 'Invoice Paid',
                description: 'Payment of $125.00 received via Card',
                time: 'Feb 15, 2024 at 2:30 PM',
                icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                iconColor: 'bg-green-100 text-green-600'
            },
            {
                title: 'Invoice Sent',
                description: 'Invoice sent to john@example.com',
                time: 'Feb 1, 2024 at 10:00 AM',
                icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>',
                iconColor: 'bg-blue-100 text-blue-600'
            },
            {
                title: 'Invoice Created',
                description: 'Invoice INV-2024-001 created for John Smith',
                time: 'Feb 1, 2024 at 9:45 AM',
                icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>',
                iconColor: 'bg-gray-100 text-gray-600'
            }
        ],

        openPaymentModal: false,
        paymentForm: {
            amount: 0,
            method: 'cash',
            date: new Date().toISOString().split('T')[0]
        },

        init() {
            this.paymentForm.amount = this.invoice.balance_due;
            this.invoice.items = this.invoice.items || [];
        },

        formatCurrency(value) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(value || 0);
        },

        getInvoiceStatusClass(status) {
            const classes = {
                draft: 'bg-gray-100 text-gray-700 border-gray-300',
                issued: 'bg-yellow-100 text-yellow-700 border-yellow-300',
                partially_paid: 'bg-blue-100 text-blue-700 border-blue-300',
                paid: 'bg-green-100 text-green-700 border-green-300',
                overdue: 'bg-red-100 text-red-700 border-red-300',
                void: 'bg-gray-100 text-gray-500 border-gray-300'
            };
            return classes[status] || 'bg-gray-100 text-gray-700 border-gray-300';
        },

        async recordPayment() {
            if (!window.billingPaymentStoreUrl) {
                alert('Payment endpoint is unavailable.');
                return;
            }

            const response = await fetch(window.billingPaymentStoreUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.billingCsrfToken
                },
                body: JSON.stringify({
                    amount: this.paymentForm.amount,
                    payment_method: this.paymentForm.method,
                    paid_at: this.paymentForm.date
                })
            });

            const data = await response.json();

            if (!response.ok) {
                alert(data.message || 'Could not record payment.');
                return;
            }

            this.invoice.paid_amount = parseFloat(data.invoice.paid_amount || 0);
            this.invoice.balance_due = parseFloat(data.invoice.balance_due || 0);
            this.invoice.status = data.invoice.status;
            this.payments.push({
                id: data.payment.id,
                payment_reference: data.payment.payment_reference,
                date: data.payment.paid_at?.split('T')[0] || this.paymentForm.date,
                method: data.payment.payment_method || this.paymentForm.method,
                amount: parseFloat(data.payment.amount || 0),
                status: data.payment.status
            });

            this.openPaymentModal = false;
        }
    }
}
