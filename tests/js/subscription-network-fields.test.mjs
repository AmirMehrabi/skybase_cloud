import assert from 'node:assert/strict';
import test from 'node:test';
import { createSubscriptionNetworkFields } from '../../resources/js/subscriptions/network-fields.js';

function networkFields(overrides = {}) {
    const form = { concurrent_devices: 3, ip_allocation: 'reserved_set', ip_pool_id: '1', reserved_ip_addresses: [], ...overrides };
    return createSubscriptionNetworkFields({ form, pools: [{ id: 1, available_addresses: ['10.0.0.21', '10.0.0.22', '10.0.0.23'].map(ip_address => ({ ip_address })) }] });
}

test('fills empty slots without replacing manually selected addresses or producing duplicates', () => {
    const component = networkFields({ reserved_ip_addresses: ['', '10.0.0.23'] });
    component.fillRemainingAddresses();
    assert.deepEqual(component.networkForm.reserved_ip_addresses, ['10.0.0.21', '10.0.0.23', '10.0.0.22']);
    assert.deepEqual(component.addressesForSlot(0).map(address => address.ip_address), ['10.0.0.21']);
    assert.equal(component.reservedCount, 3);
});

test('exhausted pools leave visible empty slots instead of inventing addresses', () => {
    const component = networkFields({ concurrent_devices: 4 });
    component.fillRemainingAddresses();
    assert.equal(component.reservedCount, 3);
    assert.equal(component.networkForm.reserved_ip_addresses[3], '');
});

test('changing device count and pools clears incompatible state', () => {
    const component = networkFields({ concurrent_devices: 1, ip_allocation: 'fixed' });
    const watchers = {};
    component.$watch = (field, callback) => { watchers[field] = callback; };
    component.init();
    component.networkForm.concurrent_devices = 2;
    watchers['networkForm.concurrent_devices']();
    assert.equal(component.networkForm.ip_allocation, 'dynamic_pool');
    assert.equal(component.networkForm.ip_management, 'system');
    component.networkForm.reserved_ip_addresses = ['10.0.0.21'];
    watchers['networkForm.ip_pool_id']();
    assert.deepEqual(component.networkForm.reserved_ip_addresses, []);
    component.networkForm.concurrent_devices = 1;
    watchers['networkForm.concurrent_devices']();
    assert.equal(component.networkForm.ip_allocation, 'fixed');
});

test('owned addresses are available only in their original pool', () => {
    const component = createSubscriptionNetworkFields({ form: { concurrent_devices: 2, ip_pool_id: '1', reserved_ip_addresses: ['10.0.0.21'] }, pools: [{ id: 1 }, { id: 2 }], originalPoolId: '1', ownedAddresses: ['10.0.0.21'] });
    assert.equal(component.networkAddresses.length, 1);
    component.networkForm.ip_pool_id = '2';
    assert.equal(component.networkAddresses.length, 0);
});
