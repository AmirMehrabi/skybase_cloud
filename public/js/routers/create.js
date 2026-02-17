// Router Create Alpine.js Component
function routerCreate() {
    return {
        form: {
            name: '',
            vendor: '',
            model: '',
            ip_address: '',
            api_port: 8728,
            api_username: 'admin',
            api_password: '',
            ssh_port: 22,
            location: '',
            site: '',
            timeout: 30,
            enable_monitoring: true,
            enable_provisioning: true
        },

        saving: false,
        testingConnection: false,
        connectionResult: null,

        // Test connection
        testConnection() {
            if (!this.form.ip_address || !this.form.api_port) {
                this.connectionResult = {
                    success: false,
                    message: 'Please enter IP address and API port first.'
                };
                setTimeout(() => this.connectionResult = null, 5000);
                return;
            }

            this.testingConnection = true;
            this.connectionResult = null;

            // Simulate API call
            setTimeout(() => {
                // Simulate random success/failure
                const success = Math.random() > 0.3;

                this.connectionResult = {
                    success: success,
                    message: success ? 'Connection successful!' : 'Connection failed!',
                    details: success
                        ? `Connected to ${this.form.ip_address}:${this.form.api_port}`
                        : 'Unable to reach router. Please check IP address, port, and credentials.'
                };

                this.testingConnection = false;

                setTimeout(() => this.connectionResult = null, 5000);
            }, 2000);
        },

        // Save router
        save() {
            // Validate required fields
            if (!this.form.name || !this.form.vendor || !this.form.ip_address) {
                alert('Please fill in all required fields.');
                return;
            }

            // Validate IP address format
            const ipPattern = /^(\d{1,3}\.){3}\d{1,3}$/;
            if (!ipPattern.test(this.form.ip_address)) {
                alert('Please enter a valid IP address.');
                return;
            }

            this.saving = true;

            // Simulate API call
            setTimeout(() => {
                this.saving = false;
                alert('Router created successfully!');
                window.location.href = '/routers';
            }, 1500);
        }
    };
}
