function paymentShow() {
    const payment = window.billingPaymentShow || {};
    return {
        payment,
        openPaymentModal: false,
        paymentForm: {
            amount: payment.amount || 0,
            method: payment.method || 'cash',
            date: payment.date || new Date().toISOString().split('T')[0]
        },

        transactionLogs: [],

        init() {
            this.paymentForm.amount = this.payment.remaining_balance || this.payment.amount || 0;
        },

        formatCurrency(value) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(value || 0);
        },

        getPaymentStatusClass(status) {
            const classes = {
                completed: 'bg-green-100 text-green-700 border-green-300',
                pending: 'bg-yellow-100 text-yellow-700 border-yellow-300',
                failed: 'bg-red-100 text-red-700 border-red-300'
            };
            return classes[status] || 'bg-gray-100 text-gray-700 border-gray-300';
        },

        async recordPayment() {
            const response = await fetch(window.billingPaymentStoreUrl || '/billing/payments', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.billingCsrfToken || ''
                },
                body: JSON.stringify({
                    invoice_id: this.payment.invoice_id,
                    amount: this.paymentForm.amount,
                    payment_method: this.paymentForm.method,
                    paid_at: this.paymentForm.date
                })
            });

            const data = await response.json();

            if (!response.ok) {
                alert(data.message || 'Unable to record payment.');
                return;
            }

            this.payment = data.payment;
            this.openPaymentModal = false;
        }
    }
}
