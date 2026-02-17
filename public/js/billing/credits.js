function credits() {
    return {
        credits: [
            { id: 1, customer_name: 'John Smith', customer_code: 'CUS-001', balance: 150.00, total_credits: 250.00, used: 100.00, last_updated: 'Feb 15, 2024', expiry_date: null },
            { id: 2, customer_name: 'Sarah Johnson', customer_code: 'CUS-002', balance: 75.00, total_credits: 75.00, used: 0, last_updated: 'Feb 10, 2024', expiry_date: '2024-12-31' },
            { id: 3, customer_name: 'Tech Corp Ltd', customer_code: 'CUS-003', balance: 0, total_credits: 500.00, used: 500.00, last_updated: 'Feb 1, 2024', expiry_date: null },
            { id: 4, customer_name: 'Mike Williams', customer_code: 'CUS-004', balance: 200.00, total_credits: 200.00, used: 0, last_updated: 'Feb 18, 2024', expiry_date: '2024-06-30' },
            { id: 5, customer_name: 'Emily Davis', customer_code: 'CUS-005', balance: 0, total_credits: 100.00, used: 100.00, last_updated: 'Jan 28, 2024', expiry_date: '2024-01-31' },
            { id: 6, customer_name: 'ABC Enterprises', customer_code: 'CUS-006', balance: 350.00, total_credits: 350.00, used: 0, last_updated: 'Feb 16, 2024', expiry_date: null },
            { id: 7, customer_name: 'Robert Brown', customer_code: 'CUS-007', balance: 50.00, total_credits: 100.00, used: 50.00, last_updated: 'Feb 12, 2024', expiry_date: '2024-09-30' },
            { id: 8, customer_name: 'Lisa Anderson', customer_code: 'CUS-008', balance: 0, total_credits: 75.00, used: 75.00, last_updated: 'Feb 8, 2024', expiry_date: null },
            { id: 9, customer_name: 'David Wilson', customer_code: 'CUS-009', balance: 125.00, total_credits: 125.00, used: 0, last_updated: 'Feb 14, 2024', expiry_date: '2024-12-31' },
            { id: 10, customer_name: 'Jennifer Taylor', customer_code: 'CUS-010', balance: 400.00, total_credits: 400.00, used: 0, last_updated: 'Feb 17, 2024', expiry_date: null }
        ],

        stats: {
            totalCredits: 1350.00,
            usedCredits: 850.00,
            availableCredits: 500.00,
            customerCount: 10
        },

        // Filters
        search: '',
        status: '',
        sortBy: 'balance_desc',

        // Modal
        openCreditModal: false,
        newCredit: {
            customer: '',
            amount: '',
            reason: 'refund',
            notes: '',
            expiry: ''
        },

        get filteredCredits() {
            let filtered = [...this.credits];

            // Search filter
            if (this.search) {
                filtered = filtered.filter(c =>
                    c.customer_name.toLowerCase().includes(this.search.toLowerCase()) ||
                    c.customer_code.toLowerCase().includes(this.search.toLowerCase())
                );
            }

            // Status filter
            if (this.status) {
                filtered = filtered.filter(c => {
                    if (this.status === 'active') return c.balance > 0;
                    if (this.status === 'zero_balance') return c.balance === 0;
                    if (this.status === 'expired') {
                        return c.expiry_date && new Date(c.expiry_date) < new Date();
                    }
                    return true;
                });
            }

            // Sort
            filtered.sort((a, b) => {
                switch (this.sortBy) {
                    case 'balance_desc': return b.balance - a.balance;
                    case 'balance_asc': return a.balance - b.balance;
                    case 'date_desc': return new Date(b.last_updated) - new Date(a.last_updated);
                    case 'name_asc': return a.customer_name.localeCompare(b.customer_name);
                    default: return 0;
                }
            });

            return filtered;
        },

        get hasActiveFilters() {
            return this.search || this.status;
        },

        formatCurrency(value) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(value || 0);
        },

        getCreditStatusLabel(credit) {
            if (credit.balance === 0) return 'Zero Balance';
            if (credit.expiry_date && new Date(credit.expiry_date) < new Date()) return 'Expired';
            return 'Active';
        },

        getCreditStatusClass(credit) {
            if (credit.balance === 0) return 'bg-gray-100 text-gray-700 border-gray-300';
            if (credit.expiry_date && new Date(credit.expiry_date) < new Date()) return 'bg-red-100 text-red-700 border-red-300';
            return 'bg-green-100 text-green-700 border-green-300';
        },

        applyCredit(credit) {
            if (credit.balance <= 0) {
                alert('No available credit to apply');
                return;
            }
            console.log('Applying credit for:', credit.customer_name);
            alert(`Credit application for ${credit.customer_name}\nAvailable: ${this.formatCurrency(credit.balance)}\n\nSelect an invoice to apply this credit to.`);
        },

        addCredit() {
            if (!this.newCredit.customer) {
                alert('Please select a customer');
                return;
            }
            if (!this.newCredit.amount || this.newCredit.amount <= 0) {
                alert('Please enter a valid amount');
                return;
            }

            console.log('Adding credit:', this.newCredit);
            alert('Credit added successfully!');
            this.openCreditModal = false;

            // Reset form
            this.newCredit = {
                customer: '',
                amount: '',
                reason: 'refund',
                notes: '',
                expiry: ''
            };
        },

        clearFilters() {
            this.search = '';
            this.status = '';
            this.sortBy = 'balance_desc';
        }
    }
}
