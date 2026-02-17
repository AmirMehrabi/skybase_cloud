function billingReports() {
    return {
        selectedPeriod: 'This Month',

        revenue: {
            total: 185450.00,
            collected: 168500.00,
            outstanding: 16950.00,
            collectionRate: 91,
            overdueInvoices: 8,
            avgCollectionDays: 12
        },

        revenueChart: [
            { month: 'Sep', revenue: 145000, collected: 138000 },
            { month: 'Oct', revenue: 158000, collected: 150000 },
            { month: 'Nov', revenue: 162000, collected: 155000 },
            { month: 'Dec', revenue: 178000, collected: 172000 },
            { month: 'Jan', revenue: 170000, collected: 162000 },
            { month: 'Feb', revenue: 185450, collected: 168500 }
        ],

        paymentMethods: [
            {
                name: 'Card',
                count: 145,
                amount: 92500.00,
                percentage: 55,
                icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>',
                bgColor: 'bg-blue-100',
                barColor: 'bg-blue-600'
            },
            {
                name: 'Bank Transfer',
                count: 68,
                amount: 48500.00,
                percentage: 29,
                icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>',
                bgColor: 'bg-purple-100',
                barColor: 'bg-purple-600'
            },
            {
                name: 'Cash',
                count: 42,
                amount: 18500.00,
                percentage: 11,
                icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>',
                bgColor: 'bg-green-100',
                barColor: 'bg-green-600'
            },
            {
                name: 'Online',
                count: 35,
                amount: 9000.00,
                percentage: 5,
                icon: '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>',
                bgColor: 'bg-cyan-100',
                barColor: 'bg-cyan-600'
            }
        ],

        topCustomers: [
            { id: 1, name: 'Tech Corp Ltd', revenue: 12500.00 },
            { id: 2, name: 'ABC Enterprises', revenue: 9800.00 },
            { id: 3, name: 'Global Tech Inc', revenue: 8500.00 },
            { id: 4, name: 'Innovation Labs', revenue: 7200.00 },
            { id: 5, name: 'Digital Solutions', revenue: 6500.00 },
            { id: 6, name: 'Cloud Services Co', revenue: 5800.00 },
            { id: 7, name: 'Network Systems', revenue: 5200.00 }
        ],

        agingReport: [
            { period: 'Current (1-30 days)', count: 23, amount: 8500.00, dotColor: 'bg-green-500', textColor: 'text-green-600', barColor: 'bg-green-500' },
            { period: '31-60 days', count: 12, amount: 4200.00, dotColor: 'bg-yellow-500', textColor: 'text-yellow-600', barColor: 'bg-yellow-500' },
            { period: '61-90 days', count: 5, amount: 2100.00, dotColor: 'bg-orange-500', textColor: 'text-orange-600', barColor: 'bg-orange-500' },
            { period: '90+ days', count: 3, amount: 2150.00, dotColor: 'bg-red-500', textColor: 'text-red-600', barColor: 'bg-red-500' }
        ],

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
