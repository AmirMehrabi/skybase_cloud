// Router Show Alpine.js Component
function routerShow() {
    const data = window.routerShowData || {};

    return {
        router: data.router || {},
        sessions: data.sessions || [],
        queues: data.queues || [],
        profiles: data.profiles || [],
        interfaces: data.interfaces || [],
        ipPools: data.ipPools || [],
        logs: data.logs || [],
        provisioningTemplates: data.provisioningTemplates || [],
        availableCustomers: data.availableCustomers || [],
        availablePlans: data.availablePlans || [],

        // Provisioning form
        provisioning: {
            customer: '',
            plan: '',
            profile: '',
            ipPool: '',
            interface: '',
            enableService: true
        },

        // Bulk provisioning form
        bulkProvision: {
            plan: '',
            profile: ''
        },

        provisioningInProgress: false,
        bulkProvisioning: false,

        // Modals
        showCreateProfileModal: false,
        showCreatePoolModal: false,

        // Reconnect router
        reconnectRouter() {
            if (confirm('Are you sure you want to reconnect to this router?')) {
                alert('Reconnecting to router...');
                // Simulate reconnection
                setTimeout(() => {
                    this.router.status = 'online';
                    alert('Reconnected successfully!');
                }, 1500);
            }
        },

        // Restart router
        restartRouter() {
            if (confirm('Are you sure you want to restart this router? This will disconnect all active sessions.')) {
                alert('Restarting router...');
                // Simulate restart
                setTimeout(() => {
                    this.router.uptime = '0d 0h';
                    this.router.cpu_usage = 5;
                    this.router.memory_usage = 25;
                    alert('Router restarted successfully!');
                }, 3000);
            }
        },

        // Disconnect session
        disconnectSession(session) {
            if (confirm(`Disconnect session for ${session.username}?`)) {
                const index = this.sessions.findIndex(s => s.id === session.id);
                if (index !== -1) {
                    this.sessions.splice(index, 1);
                    this.router.active_sessions_count--;
                }
            }
        },

        // Toggle queue status
        toggleQueueStatus(queue) {
            const index = this.queues.findIndex(q => q.id === queue.id);
            if (index !== -1) {
                this.queues[index].status = queue.status === 'active' ? 'disabled' : 'active';
            }
        },

        // Delete queue
        deleteQueue(queue) {
            if (confirm(`Delete queue "${queue.name}"?`)) {
                const index = this.queues.findIndex(q => q.id === queue.id);
                if (index !== -1) {
                    this.queues.splice(index, 1);
                }
            }
        },

        // Delete profile
        deleteProfile(profile) {
            if (confirm(`Delete profile "${profile.name}"? This may affect active customers.`)) {
                const index = this.profiles.findIndex(p => p.id === profile.id);
                if (index !== -1) {
                    this.profiles.splice(index, 1);
                }
            }
        },

        // Delete IP pool
        deletePool(pool) {
            if (confirm(`Delete IP pool "${pool.name}"? This may affect active assignments.`)) {
                const index = this.ipPools.findIndex(p => p.id === pool.id);
                if (index !== -1) {
                    this.ipPools.splice(index, 1);
                }
            }
        },

        // Provision customer
        provisionCustomer() {
            if (!this.provisioning.customer || !this.provisioning.plan || !this.provisioning.profile) {
                alert('Please select customer, plan, and profile.');
                return;
            }

            this.provisioningInProgress = true;

            // Simulate API call
            setTimeout(() => {
                this.provisioningInProgress = false;
                alert('Customer provisioned successfully!');

                // Reset form
                this.provisioning = {
                    customer: '',
                    plan: '',
                    profile: '',
                    ipPool: '',
                    interface: '',
                    enableService: true
                };
            }, 2000);
        },

        // Bulk provision customers
        bulkProvisionCustomers() {
            if (!this.bulkProvision.plan || !this.bulkProvision.profile) {
                alert('Please select plan and profile.');
                return;
            }

            if (confirm('This will apply the selected plan to all pending customers. Continue?')) {
                this.bulkProvisioning = true;

                // Simulate API call
                setTimeout(() => {
                    this.bulkProvisioning = false;
                    alert('Bulk provisioning completed successfully!');

                    // Reset form
                    this.bulkProvision = {
                        plan: '',
                        profile: ''
                    };
                }, 3000);
            }
        },
    };
}
