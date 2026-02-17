function invoicesIndex() {
    return {
        invoices: [
            { id: 1, invoice_number: 'INV-2024-001', customer_name: 'John Smith', subscription_code: 'SUB-001', issue_date: '2024-02-01', due_date: '2024-02-28', billing_period: 'Feb 2024', subtotal: 113.64, tax: 11.36, discount: 0, total: 125.00, paid_amount: 125.00, balance_due: 0, status: 'paid' },
            { id: 2, invoice_number: 'INV-2024-002', customer_name: 'Sarah Johnson', subscription_code: 'SUB-002', issue_date: '2024-02-01', due_date: '2024-02-25', billing_period: 'Feb 2024', subtotal: 80.91, tax: 8.09, discount: 0, total: 89.00, paid_amount: 0, balance_due: 89.00, status: 'overdue' },
            { id: 3, invoice_number: 'INV-2024-003', customer_name: 'Tech Corp Ltd', subscription_code: 'SUB-003', issue_date: '2024-02-05', due_date: '2024-03-01', billing_period: 'Feb 2024', subtotal: 318.18, tax: 31.82, discount: 0, total: 350.00, paid_amount: 0, balance_due: 350.00, status: 'unpaid' },
            { id: 4, invoice_number: 'INV-2024-004', customer_name: 'Mike Williams', subscription_code: 'SUB-004', issue_date: '2024-02-01', due_date: '2024-02-20', billing_period: 'Feb 2024', subtotal: 68.18, tax: 6.82, discount: 0, total: 75.00, paid_amount: 0, balance_due: 75.00, status: 'overdue' },
            { id: 5, invoice_number: 'INV-2024-005', customer_name: 'Emily Davis', subscription_code: 'SUB-005', issue_date: '2024-02-08', due_date: '2024-03-05', billing_period: 'Feb 2024', subtotal: 136.36, tax: 13.64, discount: 0, total: 150.00, paid_amount: 0, balance_due: 150.00, status: 'draft' },
            { id: 6, invoice_number: 'INV-2024-006', customer_name: 'ABC Enterprises', subscription_code: 'SUB-006', issue_date: '2024-02-01', due_date: '2024-02-28', billing_period: 'Feb 2024', subtotal: 454.55, tax: 45.45, discount: 0, total: 500.00, paid_amount: 500.00, balance_due: 0, status: 'paid' },
            { id: 7, invoice_number: 'INV-2024-007', customer_name: 'Robert Brown', subscription_code: 'SUB-007', issue_date: '2024-02-06', due_date: '2024-03-10', billing_period: 'Feb 2024', subtotal: 90.00, tax: 9.00, discount: 0, total: 99.00, paid_amount: 0, balance_due: 99.00, status: 'unpaid' },
            { id: 8, invoice_number: 'INV-2024-008', customer_name: 'Lisa Anderson', subscription_code: 'SUB-008', issue_date: '2024-02-07', due_date: '2024-03-07', billing_period: 'Feb 2024', subtotal: 227.27, tax: 22.73, discount: 0, total: 250.00, paid_amount: 100.00, balance_due: 150.00, status: 'unpaid' },
            { id: 9, invoice_number: 'INV-2024-009', customer_name: 'David Wilson', subscription_code: 'SUB-009', issue_date: '2024-02-08', due_date: '2024-02-25', billing_period: 'Feb 2024', subtotal: 54.55, tax: 5.45, discount: 0, total: 60.00, paid_amount: 0, balance_due: 60.00, status: 'overdue' },
            { id: 10, invoice_number: 'INV-2024-010', customer_name: 'Jennifer Taylor', subscription_code: 'SUB-010', issue_date: '2024-02-09', due_date: '2024-03-09', billing_period: 'Feb 2024', subtotal: 181.82, tax: 18.18, discount: 0, total: 200.00, paid_amount: 200.00, balance_due: 0, status: 'paid' },
            { id: 11, invoice_number: 'INV-2024-011', customer_name: 'Chris Martinez', subscription_code: 'SUB-011', issue_date: '2024-02-10', due_date: '2024-03-10', billing_period: 'Feb 2024', subtotal: 127.27, tax: 12.73, discount: 0, total: 140.00, paid_amount: 0, balance_due: 140.00, status: 'unpaid' },
            { id: 12, invoice_number: 'INV-2024-012', customer_name: 'Amanda White', subscription_code: 'SUB-012', issue_date: '2024-02-11', due_date: '2024-02-28', billing_period: 'Feb 2024', subtotal: 163.64, tax: 16.36, discount: 0, total: 180.00, paid_amount: 180.00, balance_due: 0, status: 'paid' },
            { id: 13, invoice_number: 'INV-2024-013', customer_name: 'Global Tech Inc', subscription_code: 'SUB-013', issue_date: '2024-02-12', due_date: '2024-03-12', billing_period: 'Feb 2024', subtotal: 727.27, tax: 72.73, discount: 50.00, total: 750.00, paid_amount: 0, balance_due: 750.00, status: 'unpaid' },
            { id: 14, invoice_number: 'INV-2024-014', customer_name: 'Tom Harris', subscription_code: 'SUB-014', issue_date: '2024-02-13', due_date: '2024-02-27', billing_period: 'Feb 2024', subtotal: 45.45, tax: 4.55, discount: 0, total: 50.00, paid_amount: 0, balance_due: 50.00, status: 'overdue' },
            { id: 15, invoice_number: 'INV-2024-015', customer_name: 'Sophie Clark', subscription_code: 'SUB-015', issue_date: '2024-02-14', due_date: '2024-03-14', billing_period: 'Feb 2024', subtotal: 218.18, tax: 21.82, discount: 0, total: 240.00, paid_amount: 0, balance_due: 240.00, status: 'unpaid' }
        ],

        // Filters
        search: '',
        status: '',
        dateFrom: '',
        dateTo: '',
        customer: '',

        // Pagination
        currentPage: 1,
        perPage: 10,

        // Payment Modal
        paymentModalOpen: false,
        selectedInvoice: null,
        paymentAmount: '',
        paymentMethod: 'cash',
        paymentDate: new Date().toISOString().split('T')[0],

        filterOptions: {
            statuses: [
                { value: 'draft', label: 'Draft' },
                { value: 'unpaid', label: 'Unpaid' },
                { value: 'paid', label: 'Paid' },
                { value: 'overdue', label: 'Overdue' },
                { value: 'cancelled', label: 'Cancelled' }
            ]
        },

        get filteredInvoices() {
            return this.invoices.filter(invoice => {
                const matchSearch = !this.search ||
                    invoice.invoice_number.toLowerCase().includes(this.search.toLowerCase()) ||
                    invoice.customer_name.toLowerCase().includes(this.search.toLowerCase());

                const matchStatus = !this.status || invoice.status === this.status;

                const matchDateFrom = !this.dateFrom || invoice.issue_date >= this.dateFrom;
                const matchDateTo = !this.dateTo || invoice.issue_date <= this.dateTo;

                const matchCustomer = !this.customer || invoice.customer_name.toLowerCase().includes(this.customer.toLowerCase());

                return matchSearch && matchStatus && matchDateFrom && matchDateTo && matchCustomer;
            });
        },

        get paginatedInvoices() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredInvoices.slice(start, start + this.perPage);
        },

        get totalInvoices() {
            return this.filteredInvoices.length;
        },

        get totalPages() {
            return Math.ceil(this.totalInvoices / this.perPage);
        },

        get displayedPages() {
            const pages = [];
            const maxPages = 5;
            let start = Math.max(1, this.currentPage - Math.floor(maxPages / 2));
            let end = Math.min(this.totalPages, start + maxPages - 1);

            if (end - start < maxPages - 1) {
                start = Math.max(1, end - maxPages + 1);
            }

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }
            return pages;
        },

        get hasActiveFilters() {
            return this.search || this.status || this.dateFrom || this.dateTo || this.customer;
        },

        get totalAmount() {
            return this.filteredInvoices.reduce((sum, inv) => sum + inv.total, 0);
        },

        get paidAmount() {
            return this.filteredInvoices.reduce((sum, inv) => sum + inv.paid_amount, 0);
        },

        get outstandingAmount() {
            return this.filteredInvoices.reduce((sum, inv) => sum + inv.balance_due, 0);
        },

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
        },

        clearFilters() {
            this.search = '';
            this.status = '';
            this.dateFrom = '';
            this.dateTo = '';
            this.customer = '';
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

        openPaymentModal(invoice) {
            this.selectedInvoice = invoice;
            this.paymentAmount = invoice.balance_due;
            this.paymentModalOpen = true;
        },

        recordPayment() {
            // In a real app, this would make an API call
            console.log('Recording payment:', {
                invoice: this.selectedInvoice?.invoice_number,
                amount: this.paymentAmount,
                method: this.paymentMethod,
                date: this.paymentDate
            });
            this.paymentModalOpen = false;
            alert('Payment recorded successfully!');
        }
    }
}
