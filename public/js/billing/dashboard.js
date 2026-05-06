function billingDashboard() {
    const dashboard = window.billingDashboard || {};
    const revenueChart = dashboard.revenueChart || [];
    const chartMax = Math.max(1, ...revenueChart.flatMap((month) => [month.revenue || 0, month.collected || 0]));

    return {
        stats: dashboard.stats || {},
        revenueChart: revenueChart.map((month) => ({
            ...month,
            revenueHeight: Math.max(((month.revenue || 0) / chartMax) * 100, 8),
            collectedHeight: Math.max(((month.collected || 0) / chartMax) * 100, 8),
        })),
        recentInvoices: dashboard.recentInvoices || [],

        formatCurrency(value) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(value);
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
        }
    }
}
