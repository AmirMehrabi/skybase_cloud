function paymentsIndex() {
    const payments = window.billingPayments || [];
    return {
        payments,
        stats: window.billingPaymentStats || {},
        customers: window.billingPaymentCustomers || [],
        invoices: window.billingPaymentInvoices || [],

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
            customer_id: '',
            invoice_id: '',
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
            if (!this.newPayment.invoice_id) {
                alert('Please select an invoice.');
                return;
            }

            fetch(window.billingPaymentStoreUrl || '/billing/payments', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.billingCsrfToken || ''
                },
                body: JSON.stringify({
                    invoice_id: this.newPayment.invoice_id,
                    amount: this.newPayment.amount,
                    payment_method: this.newPayment.method,
                    paid_at: this.newPayment.date,
                    customer_id: this.newPayment.customer_id
                })
            }).then(async (response) => {
                const data = await response.json();

                if (!response.ok) {
                    alert(data.message || 'Unable to record payment.');
                    return;
                }

                this.payments.unshift(data.payment);
                this.stats.totalCollected = (this.stats.totalCollected || 0) + (parseFloat(this.newPayment.amount) || 0);
                this.stats.totalCount = (this.stats.totalCount || 0) + 1;
                this.openRecordPaymentModal = false;
                this.newPayment = {
                    customer_id: '',
                    invoice_id: '',
                    amount: '',
                    method: 'cash',
                    date: new Date().toISOString().split('T')[0]
                };
            });
        }
    }
}
