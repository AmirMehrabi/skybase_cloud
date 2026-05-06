function credits() {
    const credits = window.billingCredits || [];
    return {
        credits,
        stats: window.billingCreditStats || {},
        customers: window.billingCreditCustomers || [],

        // Filters
        search: '',
        status: '',
        sortBy: 'balance_desc',

        // Modal
        openCreditModal: false,
        newCredit: {
            customer_id: '',
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
            alert(`Credit available for ${credit.customer_name}: ${this.formatCurrency(credit.balance)}`);
        },

        addCredit() {
            if (!this.newCredit.customer_id) {
                alert('Please select a customer');
                return;
            }
            if (!this.newCredit.amount || this.newCredit.amount <= 0) {
                alert('Please enter a valid amount');
                return;
            }

            fetch(window.billingCreditStoreUrl || '/billing/credits', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.billingCsrfToken || ''
                },
                body: JSON.stringify({
                    customer_id: this.newCredit.customer_id,
                    amount: this.newCredit.amount,
                    reason: this.newCredit.reason,
                    notes: this.newCredit.notes,
                    expires_at: this.newCredit.expiry || null
                })
            }).then(async (response) => {
                const data = await response.json();
                if (!response.ok) {
                    alert(data.message || 'Unable to add credit.');
                    return;
                }

                this.credits.unshift(data.credit);
                this.stats.totalCredits = (this.stats.totalCredits || 0) + parseFloat(this.newCredit.amount);
                this.stats.availableCredits = (this.stats.availableCredits || 0) + parseFloat(this.newCredit.amount);
                this.openCreditModal = false;
                this.newCredit = {
                    customer_id: '',
                    amount: '',
                    reason: 'refund',
                    notes: '',
                    expiry: ''
                };
            });
        },

        clearFilters() {
            this.search = '';
            this.status = '';
            this.sortBy = 'balance_desc';
        }
    }
}
