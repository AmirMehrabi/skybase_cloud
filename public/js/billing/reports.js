function billingReports() {
    const reports = window.billingReports || {};
    const periodLabels = {
        this_month: 'This Month',
        last_month: 'Last Month',
        this_quarter: 'This Quarter',
        this_year: 'This Year',
        custom: 'Custom Range'
    };
    return {
        selectedPeriod: periodLabels[reports.period] || 'This Month',
        revenue: reports.revenue || {},
        revenueChart: reports.revenueChart || [],
        paymentMethods: reports.paymentMethods || [],
        topCustomers: reports.topCustomers || [],
        agingReport: reports.agingReport || [],

        get totalAging() {
            return this.agingReport.reduce((sum, aging) => sum + aging.amount, 0);
        },

        formatCurrency(value) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(value || 0);
        }
    }
}
