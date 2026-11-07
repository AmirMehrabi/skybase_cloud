import test from 'node:test';
import assert from 'node:assert/strict';
globalThis.window = {};
await import('../resources/js/site-coverage.js');
const make = () => { const state = window.siteCoverage({ areas: [], latitude: 35, longitude: 51, saveUrl: '/coverage', csrf: 'test' }); state.reset(); return state; };
test('polygon drawing supports undo, exclusion and disconnected areas', () => {
    const state = make();
    state.draw('polygon'); state.points = [[51,35],[52,35],[52,36]]; state.finish();
    assert.equal(state.form.geometry.coordinates[0].length, 4);
    state.draw('hole'); state.points = [[51.5,35.2],[51.6,35.2],[51.6,35.3]]; state.finish();
    assert.equal(state.form.geometry.coordinates.length, 2);
    state.undo(); assert.equal(state.form.geometry.coordinates.length, 1);
    state.draw('part'); state.points = [[53,35],[54,35],[54,36]]; state.finish();
    assert.equal(state.form.geometry.type, 'MultiPolygon'); assert.equal(state.form.geometry.coordinates.length, 2);
});
test('sector geometry remains closed and is marked as estimated', () => {
    const state = make(); state.form.shape.type = 'sector'; state.generate();
    const ring = state.form.geometry.coordinates[0];
    assert.deepEqual(ring[0], ring.at(-1)); assert.equal(state.form.evidence, 'estimated');
    assert.match(state.measurements(), /km²/);
});
test('restoring a published revision creates an unsaved draft', () => {
    const state = make(); state.form.shape.type = 'circle'; state.generate(); state.dirty = false;
    state.restore({ snapshot: { ...state.form, status: 'published' } });
    assert.equal(state.form.status, 'draft'); assert.equal(state.dirty, true);
});
test('failed saves retain work and release the busy state', async () => {
    const state = make(); state.form.name = 'Keep me'; state.dirty = true;
    globalThis.fetch = async () => ({ ok: false, json: async () => ({ message: 'Conflict: reload first' }) });
    await state.save('published');
    assert.equal(state.form.name, 'Keep me'); assert.equal(state.dirty, true); assert.equal(state.busy, false); assert.match(state.error, /Conflict/);
});
