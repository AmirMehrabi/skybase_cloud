export function createSubscriptionNetworkFields({ form, pools, originalPoolId = '', ownedAddresses = [] }) {
    return {
        networkForm: form,
        networkPools: pools,
        originalPoolId: String(originalPoolId),
        ownedAddresses,
        init() {
            this.$watch('networkForm.concurrent_devices', () => {
                if (Number(this.networkForm.concurrent_devices) > 1) {
                    this.networkForm.reserved_ip_addresses = this.networkForm.reserved_ip_addresses.slice(0, Number(this.networkForm.concurrent_devices));
                    this.networkForm.ip_management = 'system';
                    if (this.networkForm.ip_allocation === 'fixed') {
                        this.networkForm.ip_allocation = 'dynamic_pool';
                    }
                } else {
                    this.networkForm.ip_allocation = 'fixed';
                    this.networkForm.reserved_ip_addresses = [];
                }
            });
            this.$watch('networkForm.ip_pool_id', () => {
                this.networkForm.reserved_ip_addresses = [];
            });
        },
        get multipleDevices() {
            return Number(this.networkForm.concurrent_devices) > 1;
        },
        get networkPool() {
            return this.networkPools.find(pool => String(pool.id) === String(this.networkForm.ip_pool_id));
        },
        get networkAddresses() {
            const addresses = [...(this.networkPool?.available_addresses || [])];
            if (String(this.networkForm.ip_pool_id) === this.originalPoolId) {
                this.ownedAddresses.forEach(ip => addresses.push({ id: `owned-${ip}`, ip_address: ip }));
            }
            return [...new Map(addresses.map(address => [address.ip_address, address])).values()];
        },
        get reservationSlots() {
            const count = Math.min(256, Math.max(1, Number(this.networkForm.concurrent_devices) || 1));
            return Array.from({ length: count }, (_, index) => index);
        },
        get reservedCount() {
            return this.networkForm.reserved_ip_addresses.filter(Boolean).length;
        },
        addressesForSlot(index) {
            return this.networkAddresses.filter(address =>
                !this.networkForm.reserved_ip_addresses.some((ip, selectedIndex) => selectedIndex !== index && ip === address.ip_address)
            );
        },
        fillRemainingAddresses() {
            const selected = this.networkForm.reserved_ip_addresses.slice(0, this.reservationSlots.length);
            const available = this.networkAddresses.map(address => address.ip_address).filter(ip => !selected.includes(ip));
            this.networkForm.reserved_ip_addresses = this.reservationSlots.map(index => selected[index] || available.shift() || '');
        },
        chooseAllocation(mode) {
            this.networkForm.ip_allocation = mode;
            if (mode !== 'reserved_set') {
                this.networkForm.reserved_ip_addresses = [];
            }
        },
        get networkSummary() {
            const count = Number(this.networkForm.concurrent_devices) || 1;
            if (count === 1) {
                return '1 concurrent device · Single fixed IP';
            }
            return `${count} concurrent devices · ${this.networkForm.ip_allocation === 'reserved_set' ? `${this.reservedCount} reserved IPs` : 'Automatic IP allocation'}`;
        },
    };
}
