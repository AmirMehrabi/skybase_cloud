document.addEventListener('alpine:init', () => {
    Alpine.data('routerEdit', () => ({
        router: {
            id: {{ $router->id }},
            name: '{{ $router->name }}',
            vendor: '{{ $router->vendor }}',
            model: '{{ $router->model ?? '' }}',
            location: '{{ $router->location ?? '' }}',
            site: '{{ $router->site ?? '' }}',
            ip_address: '{{ $router->ip_address }}',
            api_port: {{ $router->api_port }},
            ssh_port: {{ $router->ssh_port }},
            api_username: '{{ $router->api_username ?? '' }}',
            timeout: {{ $router->timeout ?? 30 }},
            enable_monitoring: {{ $router->enable_monitoring ? 'true' : 'false' }},
            enable_provisioning: {{ $router->enable_provisioning ? 'true' : 'false' }},
        },
        form: {
            name: '',
            vendor: '',
            model: '',
            location: '',
            site: '',
            ip_address: '',
            api_port: 8728,
            ssh_port: 22,
            api_username: '',
            api_password: '',
            timeout: 30,
            enable_monitoring: false,
            enable_provisioning: false,
        },
        testingConnection: false,
        connectionResult: null,
        saving: false,
        errors: {},

        init() {
            // Initialize form with router data
            this.form = {
                name: this.router.name,
                vendor: this.router.vendor,
                model: this.router.model,
                location: this.router.location,
                site: this.router.site,
                ip_address: this.router.ip_address,
                api_port: this.router.api_port,
                ssh_port: this.router.ssh_port,
                api_username: this.router.api_username,
                api_password: '',
                timeout: this.router.timeout,
                enable_monitoring: this.router.enable_monitoring,
                enable_provisioning: this.router.enable_provisioning,
            };
        },

        async testConnection() {
            this.testingConnection = true;
            this.connectionResult = null;

            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 1500));

            const success = Math.random() > 0.3;

            this.connectionResult = {
                success: success,
                message: success
                    ? 'Connection successful! Router is reachable.'
                    : 'Connection failed. Please check your credentials.',
                details: success
                    ? `Response time: ${Math.floor(Math.random() * 50 + 10)}ms`
                    : 'Error: Authentication failed or timeout exceeded.',
            };

            this.testingConnection = false;
        },

        async save() {
            if (!this.validate()) {
                return;
            }

            this.saving = true;

            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 1000));

            // In real app, submit form to backend
            window.location.href = `/routers/${this.router.id}`;
        },

        validate() {
            this.errors = {};

            if (!this.form.name) {
                this.errors.name = 'Router name is required';
            }

            if (!this.form.vendor) {
                this.errors.vendor = 'Vendor is required';
            }

            if (!this.form.ip_address) {
                this.errors.ip_address = 'IP address is required';
            } else if (!this.isValidIP(this.form.ip_address)) {
                this.errors.ip_address = 'Invalid IP address format';
            }

            if (!this.form.api_port) {
                this.errors.api_port = 'API port is required';
            }

            if (!this.form.ssh_port) {
                this.errors.ssh_port = 'SSH port is required';
            }

            return Object.keys(this.errors).length === 0;
        },

        isValidIP(ip) {
            const ipPattern = /^(\d{1,3}\.){3}\d{1,3}$/;
            if (!ipPattern.test(ip)) return false;

            const parts = ip.split('.');
            return parts.every(part => {
                const num = parseInt(part);
                return num >= 0 && num <= 255;
            });
        },
    });
});
