document.addEventListener('alpine:init', () => {
    Alpine.data('routerProfiles', () => ({
        router: {
            id: 1,
            name: 'Core-Router-1'
        },
        profiles: [],
        showCreateModal: false,
        editingProfile: null,
        profileForm: {
            name: '',
            download: '',
            upload: '',
            on_login: '',
            on_logout: ''
        },

        init() {
            // Load profiles from shared data or create defaults
            if (typeof routerProfiles !== 'undefined') {
                this.profiles = routerProfiles;
            } else {
                this.profiles = [
                    {
                        id: 'default',
                        name: 'default',
                        rate_limit: 'Unlimited',
                        active_users: 42,
                        status: 'active',
                        on_login: '',
                        on_logout: ''
                    },
                    {
                        id: '10Mbps',
                        name: '10Mbps-Unlimited',
                        rate_limit: '10M/5M',
                        active_users: 38,
                        status: 'active',
                        on_login: 'add-default-route',
                        on_logout: ''
                    },
                    {
                        id: '25Mbps',
                        name: '25Mbps-Unlimited',
                        rate_limit: '25M/15M',
                        active_users: 25,
                        status: 'active',
                        on_login: '',
                        on_logout: ''
                    },
                    {
                        id: '50Mbps',
                        name: '50Mbps-Unlimited',
                        rate_limit: '50M/25M',
                        active_users: 15,
                        status: 'active',
                        on_login: 'add-default-route',
                        on_logout: 'rem-default-route'
                    },
                    {
                        id: '100Mbps',
                        name: '100Mbps-Fiber',
                        rate_limit: '100M/50M',
                        active_users: 7,
                        status: 'active',
                        on_login: '',
                        on_logout: ''
                    }
                ];
            }
        },

        refreshProfiles() {
            // Simulate refresh
            console.log('Refreshing profiles...');
        },

        editProfile(profile) {
            this.editingProfile = profile;
            this.profileForm = {
                name: profile.name,
                download: profile.rate_limit.split('/')[0] || '',
                upload: profile.rate_limit.split('/')[1] || '',
                on_login: profile.on_login,
                on_logout: profile.on_logout
            };
            this.showCreateModal = true;
        },

        viewUsers(profile) {
            // Navigate to sessions filtered by profile
            window.location.href = `/routers/${this.router.id}/sessions?profile=${profile.id}`;
        },

        async deleteProfile(profile) {
            if (!confirm(`Are you sure you want to delete profile "${profile.name}"?`)) {
                return;
            }

            // Simulate delete
            this.profiles = this.profiles.filter(p => p.id !== profile.id);
        },

        async saveProfile() {
            if (!this.profileForm.name) {
                alert('Profile name is required');
                return;
            }

            const rateLimit = `${this.profileForm.download}/${this.profileForm.upload}`;

            if (this.editingProfile) {
                // Update existing
                const index = this.profiles.findIndex(p => p.id === this.editingProfile.id);
                if (index !== -1) {
                    this.profiles[index] = {
                        ...this.profiles[index],
                        name: this.profileForm.name,
                        rate_limit: rateLimit,
                        on_login: this.profileForm.on_login,
                        on_logout: this.profileForm.on_logout
                    };
                }
            } else {
                // Create new
                this.profiles.push({
                    id: this.profileForm.name.toLowerCase().replace(/[^a-z0-9]/g, '-'),
                    name: this.profileForm.name,
                    rate_limit: rateLimit,
                    active_users: 0,
                    status: 'active',
                    on_login: this.profileForm.on_login,
                    on_logout: this.profileForm.on_logout
                });
            }

            this.closeModal();
        },

        closeModal() {
            this.showCreateModal = false;
            this.editingProfile = null;
            this.profileForm = {
                name: '',
                download: '',
                upload: '',
                on_login: '',
                on_logout: ''
            };
        }
    });
});
