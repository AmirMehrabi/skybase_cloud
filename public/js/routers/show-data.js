// Dummy data for router dashboard
window.routerShowData = {
    // Router info
    router: {
        id: 1,
        name: 'Core-Router-1',
        model: 'CCR1036-12G-4S',
        vendor: 'Mikrotik',
        ip_address: '192.168.1.1',
        api_port: 8728,
        api_username: 'admin',
        ssh_port: 22,
        location: 'Data Center',
        site: 'Main Site',
        status: 'online',
        version: 'v7.12',
        uptime: '25d 18h',
        cpu_usage: 45,
        memory_usage: 62,
        active_sessions_count: 127,
        total_customers: 180,
        enable_monitoring: true,
        enable_provisioning: true
    },

    // PPP Sessions (Active Connections)
    sessions: [
        { id: 1, username: 'john.doe', customer: 'John Doe', ip_address: '10.0.1.10', mac_address: 'AA:BB:CC:DD:EE:01', download_speed: '45.2 Mbps', upload_speed: '12.8 Mbps', session_time: '2h 34m', interface: 'ether1-wan1' },
        { id: 2, username: 'jane.smith', customer: 'Jane Smith', ip_address: '10.0.1.11', mac_address: 'AA:BB:CC:DD:EE:02', download_speed: '95.8 Mbps', upload_speed: '48.2 Mbps', session_time: '5h 12m', interface: 'ether1-wan1' },
        { id: 3, username: 'bob.wilson', customer: 'Bob Wilson', ip_address: '10.0.1.12', mac_address: 'AA:BB:CC:DD:EE:03', download_speed: '12.3 Mbps', upload_speed: '3.1 Mbps', session_time: '45m', interface: 'ether2-wan2' },
        { id: 4, username: 'alice.brown', customer: 'Alice Brown', ip_address: '10.0.1.13', mac_address: 'AA:BB:CC:DD:EE:04', download_speed: '78.5 Mbps', upload_speed: '25.4 Mbps', session_time: '1h 20m', interface: 'ether1-wan1' },
        { id: 5, username: 'charlie.davis', customer: 'Charlie Davis', ip_address: '10.0.1.14', mac_address: 'AA:BB:CC:DD:EE:05', download_speed: '32.1 Mbps', upload_speed: '8.9 Mbps', session_time: '3h 45m', interface: 'ether2-wan2' },
        { id: 6, username: 'diana.miller', customer: 'Diana Miller', ip_address: '10.0.1.15', mac_address: 'AA:BB:CC:DD:EE:06', download_speed: '156.2 Mbps', upload_speed: '78.9 Mbps', session_time: '8h 30m', interface: 'ether1-wan1' },
        { id: 7, username: 'evan.garcia', customer: 'Evan Garcia', ip_address: '10.0.1.16', mac_address: 'AA:BB:CC:DD:EE:07', download_speed: '23.7 Mbps', upload_speed: '6.2 Mbps', session_time: '1h 05m', interface: 'ether3-wan3' },
        { id: 8, username: 'fiona Martinez', customer: 'Fiona Martinez', ip_address: '10.0.1.17', mac_address: 'AA:BB:CC:DD:EE:08', download_speed: '67.8 Mbps', upload_speed: '22.1 Mbps', session_time: '4h 15m', interface: 'ether1-wan1' },
        { id: 9, username: 'george.lee', customer: 'George Lee', ip_address: '10.0.1.18', mac_address: 'AA:BB:CC:DD:EE:09', download_speed: '8.4 Mbps', upload_speed: '1.2 Mbps', session_time: '30m', interface: 'ether2-wan2' },
        { id: 10, username: 'hannah.white', customer: 'Hannah White', ip_address: '10.0.1.19', mac_address: 'AA:BB:CC:DD:EE:10', download_speed: '112.5 Mbps', upload_speed: '56.8 Mbps', session_time: '6h 20m', interface: 'ether1-wan1' },
        { id: 11, username: 'ivan.petrov', customer: 'Ivan Petrov', ip_address: '10.0.1.20', mac_address: 'AA:BB:CC:DD:EE:11', download_speed: '44.1 Mbps', upload_speed: '11.5 Mbps', session_time: '2h 10m', interface: 'ether3-wan3' },
        { id: 12, username: 'julia.chen', customer: 'Julia Chen', ip_address: '10.0.1.21', mac_address: 'AA:BB:CC:DD:EE:12', download_speed: '89.3 Mbps', upload_speed: '44.7 Mbps', session_time: '3h 55m', interface: 'ether1-wan1' },
        { id: 13, username: 'kevin.johnson', customer: 'Kevin Johnson', ip_address: '10.0.1.22', mac_address: 'AA:BB:CC:DD:EE:13', download_speed: '55.6 Mbps', upload_speed: '14.2 Mbps', session_time: '1h 40m', interface: 'ether2-wan2' },
        { id: 14, username: 'laura.williams', customer: 'Laura Williams', ip_address: '10.0.1.23', mac_address: 'AA:BB:CC:DD:EE:14', download_speed: '102.8 Mbps', upload_speed: '51.4 Mbps', session_time: '7h 25m', interface: 'ether1-wan1' },
        { id: 15, username: 'michael.brown', customer: 'Michael Brown', ip_address: '10.0.1.24', mac_address: 'AA:BB:CC:DD:EE:15', download_speed: '37.2 Mbps', upload_speed: '9.8 Mbps', session_time: '2h 50m', interface: 'ether3-wan3' },
    ],

    // Queues
    queues: [
        { id: 1, name: 'queue-john-doe', target: '10.0.1.10', download_limit: '50Mbps', upload_limit: '20Mbps', burst_limit: '60Mbps', status: 'active' },
        { id: 2, name: 'queue-jane-smith', target: '10.0.1.11', download_limit: '100Mbps', upload_limit: '50Mbps', burst_limit: '120Mbps', status: 'active' },
        { id: 3, name: 'queue-bob-wilson', target: '10.0.1.12', download_limit: '25Mbps', upload_limit: '10Mbps', burst_limit: '30Mbps', status: 'active' },
        { id: 4, name: 'queue-alice-brown', target: '10.0.1.13', download_limit: '100Mbps', upload_limit: '50Mbps', burst_limit: '120Mbps', status: 'active' },
        { id: 5, name: 'queue-charlie-davis', target: '10.0.1.14', download_limit: '50Mbps', upload_limit: '20Mbps', burst_limit: '60Mbps', status: 'disabled' },
        { id: 6, name: 'queue-diana-miller', target: '10.0.1.15', download_limit: '200Mbps', upload_limit: '100Mbps', burst_limit: '250Mbps', status: 'active' },
        { id: 7, name: 'queue-evan-garcia', target: '10.0.1.16', download_limit: '50Mbps', upload_limit: '20Mbps', burst_limit: '60Mbps', status: 'active' },
        { id: 8, name: 'queue-fiona-martinez', target: '10.0.1.17', download_limit: '100Mbps', upload_limit: '50Mbps', burst_limit: '120Mbps', status: 'active' },
    ],

    // PPP Profiles
    profiles: [
        { id: 1, name: 'Profile-50Mbps', download_speed: '50Mbps', upload_speed: '20Mbps', priority: '5', rate_limit: '50M/20M', active_users: 45 },
        { id: 2, name: 'Profile-100Mbps', download_speed: '100Mbps', upload_speed: '50Mbps', priority: '4', rate_limit: '100M/50M', active_users: 52 },
        { id: 3, name: 'Profile-200Mbps', download_speed: '200Mbps', upload_speed: '100Mbps', priority: '3', rate_limit: '200M/100M', active_users: 18 },
        { id: 4, name: 'Profile-500Mbps', download_speed: '500Mbps', upload_speed: '250Mbps', priority: '2', rate_limit: '500M/250M', active_users: 8 },
        { id: 5, name: 'Profile-1Gbps', download_speed: '1Gbps', upload_speed: '500Mbps', priority: '1', rate_limit: '1G/500M', active_users: 4 },
        { id: 6, name: 'Profile-Basic', download_speed: '25Mbps', upload_speed: '10Mbps', priority: '8', rate_limit: '25M/10M', active_users: 23 },
        { id: 7, name: 'Profile-Premium', download_speed: '300Mbps', upload_speed: '150Mbps', priority: '2', rate_limit: '300M/150M', active_users: 12 },
        { id: 8, name: 'Profile-Unlimited', download_speed: '1Gbps', upload_speed: '1Gbps', priority: '1', rate_limit: '1G/1G', active_users: 3 },
    ],

    // Interfaces
    interfaces: [
        { id: 1, name: 'ether1-wan1', type: 'ether', status: 'active', tx_rate: '125.5 Mbps', rx_rate: '580.2 Mbps', mac_address: 'AA:BB:CC:DD:EE:F1' },
        { id: 2, name: 'ether2-wan2', type: 'ether', status: 'active', tx_rate: '45.2 Mbps', rx_rate: '320.8 Mbps', mac_address: 'AA:BB:CC:DD:EE:F2' },
        { id: 3, name: 'ether3-wan3', type: 'ether', status: 'active', tx_rate: '28.7 Mbps', rx_rate: '185.4 Mbps', mac_address: 'AA:BB:CC:DD:EE:F3' },
        { id: 4, name: 'bridge-local', type: 'bridge', status: 'active', tx_rate: '199.4 Mbps', rx_rate: '1086.4 Mbps', mac_address: 'AA:BB:CC:DD:EE:F0' },
        { id: 5, name: 'wlan1', type: 'wlan', status: 'running', tx_rate: '12.3 Mbps', rx_rate: '45.6 Mbps', mac_address: 'AA:BB:CC:DD:EE:F5' },
        { id: 6, name: 'ether4-unused', type: 'ether', status: 'inactive', tx_rate: '0 bps', rx_rate: '0 bps', mac_address: 'AA:BB:CC:DD:EE:F4' },
    ],

    // IP Pools
    ipPools: [
        { id: 1, name: 'pool-main', range_start: '10.0.1.1', range_end: '10.0.1.254', used_ips: 127, available_ips: 127 },
        { id: 2, name: 'pool-vpn', range_start: '10.0.2.1', range_end: '10.0.2.100', used_ips: 15, available_ips: 85 },
        { id: 3, name: 'pool-dmz', range_start: '10.0.3.1', range_end: '10.0.3.50', used_ips: 8, available_ips: 42 },
        { id: 4, name: 'pool-guest', range_start: '10.0.4.1', range_end: '10.0.4.200', used_ips: 35, available_ips: 165 },
        { id: 5, name: 'pool-static', range_start: '10.0.5.1', range_end: '10.0.5.100', used_ips: 45, available_ips: 55 },
    ],

    // Logs
    logs: [
        { id: 1, timestamp: '2025-02-17 14:32:15', event_type: 'info', message: 'User john.doe logged in successfully', severity: 'info' },
        { id: 2, timestamp: '2025-02-17 14:28:42', event_type: 'warning', message: 'CPU usage above 40% for 5 minutes', severity: 'warning' },
        { id: 3, timestamp: '2025-02-17 14:25:10', event_type: 'info', message: 'New session established for jane.smith', severity: 'info' },
        { id: 4, timestamp: '2025-02-17 14:20:33', event_type: 'error', message: 'Failed authentication attempt from 192.168.1.50', severity: 'error' },
        { id: 5, timestamp: '2025-02-17 14:15:08', event_type: 'info', message: 'Profile-100Mbps updated by admin', severity: 'info' },
        { id: 6, timestamp: '2025-02-17 14:10:55', event_type: 'info', message: 'Queue queue-charlie-davis disabled', severity: 'info' },
        { id: 7, timestamp: '2025-02-17 14:05:22', event_type: 'warning', message: 'Memory usage above 60%', severity: 'warning' },
        { id: 8, timestamp: '2025-02-17 14:00:00', event_type: 'info', message: 'System backup completed successfully', severity: 'info' },
        { id: 9, timestamp: '2025-02-17 13:55:18', event_type: 'info', message: 'User bob.wilson disconnected', severity: 'info' },
        { id: 10, timestamp: '2025-02-17 13:50:41', event_type: 'error', message: 'Interface ether5 link down', severity: 'error' },
    ],

    // Provisioning Templates
    provisioningTemplates: [
        { id: 1, name: 'Basic Home', download_speed: '50Mbps', upload_speed: '20Mbps', priority: '5', assigned_customers: 45 },
        { id: 2, name: 'Standard Home', download_speed: '100Mbps', upload_speed: '50Mbps', priority: '4', assigned_customers: 52 },
        { id: 3, name: 'Premium Home', download_speed: '200Mbps', upload_speed: '100Mbps', priority: '3', assigned_customers: 18 },
        { id: 4, name: 'Ultra Home', download_speed: '500Mbps', upload_speed: '250Mbps', priority: '2', assigned_customers: 8 },
        { id: 5, name: 'Business Basic', download_speed: '100Mbps', upload_speed: '100Mbps', priority: '2', assigned_customers: 15 },
        { id: 6, name: 'Business Pro', download_speed: '500Mbps', upload_speed: '500Mbps', priority: '1', assigned_customers: 6 },
    ],

    // Available customers for provisioning
    availableCustomers: [
        { id: 1, name: 'New Customer 1', email: 'new1@example.com' },
        { id: 2, name: 'New Customer 2', email: 'new2@example.com' },
        { id: 3, name: 'New Customer 3', email: 'new3@example.com' },
        { id: 4, name: 'New Customer 4', email: 'new4@example.com' },
        { id: 5, name: 'New Customer 5', email: 'new5@example.com' },
    ],

    // Available plans for provisioning
    availablePlans: [
        { id: 1, name: 'Fiber 50 Mbps', download_speed: '50Mbps', upload_speed: '20Mbps' },
        { id: 2, name: 'Fiber 100 Mbps', download_speed: '100Mbps', upload_speed: '50Mbps' },
        { id: 3, name: 'Fiber 200 Mbps', download_speed: '200Mbps', upload_speed: '100Mbps' },
        { id: 4, name: 'Fiber 500 Mbps', download_speed: '500Mbps', upload_speed: '250Mbps' },
        { id: 5, name: 'Fiber 1 Gbps', download_speed: '1Gbps', upload_speed: '500Mbps' },
    ],
};
