function billingDashboard() {
    const dashboard = window.billingDashboard || {};
    const revenueChart = dashboard.revenueChart || [];
    const toFiniteNumber = (value) => {
        const number = Number(value);

        return Number.isFinite(number) ? number : 0;
    };
    const stats = {
        revenue: 0,
        outstanding: 0,
        overdue: 0,
        paidInvoices: 0,
        unpaidInvoices: 0,
        overdueInvoices: 0,
        pendingInvoices: 0,
        customersWithBalance: 0,
        ...(dashboard.stats || {}),
    };
    const chartMax = Math.max(1, ...revenueChart.flatMap((month) => [
        toFiniteNumber(month.revenue),
        toFiniteNumber(month.collected),
    ]));

    return {
        stats,
        revenueChart: revenueChart.map((month) => ({
            ...month,
            revenue: toFiniteNumber(month.revenue),
            collected: toFiniteNumber(month.collected),
            revenueHeight: Math.max((toFiniteNumber(month.revenue) / chartMax) * 100, 8),
            collectedHeight: Math.max((toFiniteNumber(month.collected) / chartMax) * 100, 8),
        })),
        recentInvoices: dashboard.recentInvoices || [],

        formatCurrency(value) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(toFiniteNumber(value));
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
