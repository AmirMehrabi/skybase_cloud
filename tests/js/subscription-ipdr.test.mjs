import assert from 'node:assert/strict';
import test from 'node:test';
import { createSubscriptionIpdr } from '../../resources/js/subscriptions/ipdr.js';

const payload = { usage: { upload_bytes: 100, download_bytes: 200 }, latest_flows: [], analytics: { time_series: [{ at: '2026-10-18T10:00:00Z', upload_bytes: 100, download_bytes: 200 }] } };

test('loads analytics through the tenant-authorized subscription endpoint with IP filters', async () => {
    const component = createSubscriptionIpdr({ url: '/subscriptions/4/ipdr' });
    component.sourceIp = '2001:db8::1';
    component.destinationIp = '1.1.1.1';
    global.fetch = async (url, options) => {
        assert.equal(options.credentials, 'same-origin');
        const query = new URL(url, 'http://localhost').searchParams;
        assert.equal(query.get('analytics'), '1');
        assert.equal(query.get('source_ip'), '2001:db8::1');
        assert.equal(query.get('destination_ip'), '1.1.1.1');
        assert.ok(Date.parse(query.get('to')) > Date.parse(query.get('from')));
        return { ok: true, json: async () => payload };
    };
    await component.load();
    assert.equal(component.report, payload);
    assert.equal(component.loading, false);
    assert.equal(component.error, '');
});

test('does not request analytics until the IPDR tab is opened', () => {
    const component = createSubscriptionIpdr({ url: '/subscriptions/4/ipdr' });
    global.window = { location: { search: '' } };
    let watch;
    component.$watch = (field, callback) => { assert.equal(field, 'tab'); watch = callback; };
    component.tab = 'overview';
    let calls = 0;
    component.load = () => calls++;
    component.init();
    assert.equal(calls, 0);
    watch('ipdr');
    assert.equal(calls, 1);
});

test('invalid dates and unavailable analytics expose an actionable error', async () => {
    const component = createSubscriptionIpdr({ url: '/subscriptions/4/ipdr' });
    component.range = 'custom';
    component.customFrom = '2026-10-18T11:00';
    component.customTo = '2026-10-18T10:00';
    global.fetch = async () => { throw new Error('must not request invalid dates'); };
    await component.load();
    assert.match(component.error, /valid start and end/);
    component.range = '1h';
    global.fetch = async () => ({ ok: false, status: 503, json: async () => ({ message: 'IPDR is temporarily unavailable.' }) });
    await component.load();
    assert.match(component.error, /unavailable/);
    assert.equal(component.loading, false);
});

test('out-of-order responses cannot overwrite a newer filter result', async () => {
    const component = createSubscriptionIpdr({ url: '/subscriptions/4/ipdr' });
    let finishOld;
    let calls = 0;
    global.fetch = async () => ++calls === 1 ? new Promise((resolve) => { finishOld = resolve; }) : { ok: true, json: async () => payload };
    const old = component.load();
    await component.load();
    finishOld({ ok: true, json: async () => ({ ...payload, obsolete: true }) });
    await old;
    assert.equal(component.report, payload);
});

test('chart paths stay finite for empty and zero traffic', () => {
    const component = createSubscriptionIpdr({ url: '/subscriptions/4/ipdr' });
    assert.equal(component.area('upload_bytes'), '');
    component.report = { analytics: { time_series: [{ upload_bytes: 0, download_bytes: 0 }] } };
    assert.doesNotMatch(component.line('upload_bytes'), /NaN|Infinity/);
    assert.equal(component.formatBytes(0), '0 B');
    assert.equal(component.barWidth({ bytes: 0 }, [{ bytes: 0 }]), '0%');
});
