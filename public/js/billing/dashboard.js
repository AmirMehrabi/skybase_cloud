function billingDashboard() {
    return {
        stats: {
            revenue: 145850.00,
            outstanding: 42350.00,
            overdue: 12850.00,
            paidInvoices: 234,
            unpaidInvoices: 45,
            overdueInvoices: 12,
            pendingInvoices: 38,
            customersWithBalance: 156
        },
        revenueChart: [
            { month: 'Sep', revenue: 125000, collected: 118000 },
            { month: 'Oct', revenue: 138000, collected: 132000 },
            { month: 'Nov', revenue: 142000, collected: 138000 },
            { month: 'Dec', revenue: 155000, collected: 150000 },
            { month: 'Jan', revenue: 148000, collected: 142000 },
            { month: 'Feb', revenue: 145850, collected: 133000 }
        ],
        recentInvoices: [
            { id: 1, invoice_number: 'INV-2024-001', customer_name: 'John Smith', subscription_code: 'SUB-001', total: 125.00, due_date: '2024-02-28', status: 'paid', balance_due: 0 },
            { id: 2, invoice_number: 'INV-2024-002', customer_name: 'Sarah Johnson', subscription_code: 'SUB-002', total: 89.00, due_date: '2024-02-25', status: 'overdue', balance_due: 89.00 },
            { id: 3, invoice_number: 'INV-2024-003', customer_name: 'Tech Corp Ltd', subscription_code: 'SUB-003', total: 350.00, due_date: '2024-03-01', status: 'unpaid', balance_due: 350.00 },
            { id: 4, invoice_number: 'INV-2024-004', customer_name: 'Mike Williams', subscription_code: 'SUB-004', total: 75.00, due_date: '2024-02-20', status: 'overdue', balance_due: 75.00 },
            { id: 5, invoice_number: 'INV-2024-005', customer_name: 'Emily Davis', subscription_code: 'SUB-005', total: 150.00, due_date: '2024-03-05', status: 'draft', balance_due: 150.00 },
            { id: 6, invoice_number: 'INV-2024-006', customer_name: 'ABC Enterprises', subscription_code: 'SUB-006', total: 500.00, due_date: '2024-02-28', status: 'paid', balance_due: 0 },
            { id: 7, invoice_number: 'INV-2024-007', customer_name: 'Robert Brown', subscription_code: 'SUB-007', total: 99.00, due_date: '2024-03-10', status: 'unpaid', balance_due: 99.00 }
        ],

        formatCurrency(value) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(value);
        },

        getInvoiceStatusClass(status) {
            const classes = {
                draft: 'bg-gray-100 text-gray-700 border-gray-300',
                unpaid: 'bg-yellow-100 text-yellow-700 border-yellow-300',
                paid: 'bg-green-100 text-green-700 border-green-300',
                overdue: 'bg-red-100 text-red-700 border-red-300',
                cancelled: 'bg-gray-100 text-gray-500 border-gray-300'
            };
            return classes[status] || 'bg-gray-100 text-gray-700 border-gray-300';
        }
    }
}
