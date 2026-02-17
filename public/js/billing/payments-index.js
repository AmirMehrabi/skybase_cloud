function paymentsIndex() {
    return {
        payments: [
            { id: 1, payment_reference: 'PAY-2024-001', customer_name: 'John Smith', invoice_number: 'INV-2024-001', invoice_id: 1, amount: 125.00, method: 'card', date: '2024-02-15', status: 'completed' },
            { id: 2, payment_reference: 'PAY-2024-002', customer_name: 'ABC Enterprises', invoice_number: 'INV-2024-006', invoice_id: 6, amount: 500.00, method: 'bank_transfer', date: '2024-02-14', status: 'completed' },
            { id: 3, payment_reference: 'PAY-2024-003', customer_name: 'Jennifer Taylor', invoice_number: 'INV-2024-010', invoice_id: 10, amount: 200.00, method: 'online', date: '2024-02-13', status: 'completed' },
            { id: 4, payment_reference: 'PAY-2024-004', customer_name: 'Amanda White', invoice_number: 'INV-2024-012', invoice_id: 12, amount: 180.00, method: 'card', date: '2024-02-12', status: 'completed' },
            { id: 5, payment_reference: 'PAY-2024-005', customer_name: 'Tech Corp Ltd', invoice_number: 'INV-2024-003', invoice_id: 3, amount: 350.00, method: 'check', date: '2024-02-20', status: 'pending' },
            { id: 6, payment_reference: 'PAY-2024-006', customer_name: 'Emily Davis', invoice_number: 'INV-2024-005', invoice_id: 5, amount: 150.00, method: 'cash', date: '2024-02-11', status: 'completed' },
            { id: 7, payment_reference: 'PAY-2024-007', customer_name: 'Lisa Anderson', invoice_number: 'INV-2024-008', invoice_id: 8, amount: 100.00, method: 'online', date: '2024-02-18', status: 'pending' },
            { id: 8, payment_reference: 'PAY-2024-008', customer_name: 'Robert Brown', invoice_number: 'INV-2024-007', invoice_id: 7, amount: 50.00, method: 'card', date: '2024-02-17', status: 'failed' },
            { id: 9, payment_reference: 'PAY-2024-009', customer_name: 'Mike Williams', invoice_number: 'INV-2024-004', invoice_id: 4, amount: 75.00, method: 'bank_transfer', date: '2024-02-16', status: 'completed' },
            { id: 10, payment_reference: 'PAY-2024-010', customer_name: 'Chris Martinez', invoice_number: 'INV-2024-011', invoice_id: 11, amount: 140.00, method: 'card', date: '2024-02-19', status: 'pending' },
            { id: 11, payment_reference: 'PAY-2024-011', customer_name: 'David Wilson', invoice_number: 'INV-2024-009', invoice_id: 9, amount: 60.00, method: 'cash', date: '2024-02-10', status: 'completed' },
            { id: 12, payment_reference: 'PAY-2024-012', customer_name: 'Sophie Clark', invoice_number: 'INV-2024-015', invoice_id: 15, amount: 240.00, method: 'online', date: '2024-02-21', status: 'pending' }
        ],

        stats: {
            totalCollected: 2565.00,
            pending: 790.00,
            pendingCount: 4,
            failed: 50.00,
            failedCount: 1,
            totalCount: 12
        },

        // Filters
        search: '',
        method: '',
        status: '',
        date: '',

        // Pagination
        currentPage: 1,
        perPage: 10,

        // Modal
        openRecordPaymentModal: false,
        newPayment: {
            customer: '',
            invoice: '',
            amount: '',
            method: 'cash',
            date: new Date().toISOString().split('T')[0]
        },

        filterOptions: {
            methods: [
                { value: 'cash', label: 'Cash' },
                { value: 'card', label: 'Card' },
                { value: 'bank_transfer', label: 'Bank Transfer' },
                { value: 'check', label: 'Check' },
                { value: 'online', label: 'Online Payment' }
            ],
            statuses: [
                { value: 'completed', label: 'Completed' },
                { value: 'pending', label: 'Pending' },
                { value: 'failed', label: 'Failed' }
            ]
        },

        get filteredPayments() {
            return this.payments.filter(payment => {
                const matchSearch = !this.search ||
                    payment.payment_reference.toLowerCase().includes(this.search.toLowerCase()) ||
                    payment.customer_name.toLowerCase().includes(this.search.toLowerCase());

                const matchMethod = !this.method || payment.method === this.method;
                const matchStatus = !this.status || payment.status === this.status;
                const matchDate = !this.date || payment.date === this.date;

                return matchSearch && matchMethod && matchStatus && matchDate;
            });
        },

        get paginatedPayments() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredPayments.slice(start, start + this.perPage);
        },

        get totalPayments() {
            return this.filteredPayments.length;
        },

        get totalPages() {
            return Math.ceil(this.totalPayments / this.perPage);
        },

        get hasActiveFilters() {
            return this.search || this.method || this.status || this.date;
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

        getMethodIcon(method) {
            const icons = {
                cash: '<svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>',
                card: '<svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>',
                bank_transfer: '<svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>',
                check: '<svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>',
                online: '<svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>'
            };
            return icons[method] || '';
        },

        clearFilters() {
            this.search = '';
            this.method = '';
            this.status = '';
            this.date = '';
            this.currentPage = 1;
        },

        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
            }
        },

        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
            }
        },

        goToPage(page) {
            this.currentPage = page;
        },

        recordPayment() {
            console.log('Recording payment:', this.newPayment);
            alert('Payment recorded successfully!');
            this.openRecordPaymentModal = false;
        }
    }
}
