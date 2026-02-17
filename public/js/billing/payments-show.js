function paymentShow() {
    return {
        payment: {
            id: 1,
            payment_reference: 'PAY-2024-001',
            customer_name: 'John Smith',
            customer_id: 'CUS-001',
            invoice_number: 'INV-2024-001',
            invoice_id: 1,
            invoice_description: 'Business Fiber 100Mbps - February 2024',
            invoice_date: 'Feb 1, 2024',
            invoice_total: 125.00,
            amount: 125.00,
            method: 'card',
            date: 'Feb 15, 2024',
            status: 'completed',
            remaining_balance: 0
        },

        transactionLogs: [
            {
                title: 'Payment Completed',
                description: 'Payment successfully processed via Card ending in 4242',
                time: 'Feb 15, 2024 at 2:30 PM',
                icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                iconColor: 'bg-green-100 text-green-600'
            },
            {
                title: 'Payment Processed',
                description: 'Transaction ID: txn_3NiUvSGKxzX9Lr9f0NzJpKZh',
                time: 'Feb 15, 2024 at 2:29 PM',
                icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>',
                iconColor: 'bg-blue-100 text-blue-600'
            },
            {
                title: 'Payment Initiated',
                description: 'Customer submitted payment of $125.00',
                time: 'Feb 15, 2024 at 2:28 PM',
                icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                iconColor: 'bg-yellow-100 text-yellow-600'
            }
        ],

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
        }
    }
}
