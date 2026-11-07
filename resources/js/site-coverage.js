window.siteCoverage = (config) => ({
    areas: config.areas, selected: null, form: {}, dirty: false, busy: false, error: '', notice: '', mode: '', points: [], undoStack: [], importText: '',
    init() {
        this.reset();
        // Leaflet objects stay outside Alpine's reactive proxy.
        this.$nextTick(() => {
            this.map = L.map(this.$refs.map).setView([config.latitude, config.longitude], 12);
            Object.defineProperty(this.map, '__v_skip', { value: true });
            L.tileLayer(config.tiles, { attribution: config.attribution, maxZoom: 19 }).addTo(this.map);
            L.circleMarker([config.latitude, config.longitude], { radius: 7 }).addTo(this.map).bindTooltip(config.name);
            this.layers = L.layerGroup().addTo(this.map);
            Object.defineProperty(this.layers, '__v_skip', { value: true });
            this.map.on('click', e => { if (this.mode) { this.points.push([e.latlng.lng, e.latlng.lat]); this.render(); } });
            this.reset();
        });
        this.leave = e => { if (this.dirty) { e.preventDefault(); e.returnValue = ''; } };
        window.addEventListener('beforeunload', this.leave);
    },
    destroy() { window.removeEventListener('beforeunload', this.leave); this.map?.remove(); },
    canLeave() { return !this.dirty || confirm('Discard your unsaved changes?'); },
    reset() {
        this.selected = null;
        this.form = { name: '', technology: 'wireless', evidence: 'declared', availability: 'survey_required', status: 'draft', access_point_id: '', notes: '', verified_on: '', geometry: null, shape: { type: 'polygon', radius: 1000, azimuth: 0, angle: 90 } };
        this.dirty = false; this.error = ''; this.mode = ''; this.points = []; this.undoStack = []; this.render();
    },
    create() { if (this.canLeave()) this.reset(); },
    open(area) {
        if (!this.canLeave()) return;
        this.selected = area;
        this.form = JSON.parse(JSON.stringify(area.revisions[0].snapshot));
        this.form.shape ||= { type: 'polygon', radius: 1000, azimuth: 0, angle: 90 };
        this.form.verified_on ||= ''; this.form.notes ||= ''; this.form.access_point_id ||= '';
        this.dirty = false; this.mode = ''; this.points = []; this.undoStack = []; this.error = ''; this.render();
        if (this.form.geometry) this.map.fitBounds(L.geoJSON(this.form.geometry).getBounds(), { padding: [25, 25] });
    },
    checkpoint() { this.undoStack.push(JSON.stringify(this.form.geometry)); this.dirty = true; },
    undo() { if (this.mode) { this.points.pop(); } else if (this.undoStack.length) { this.form.geometry = JSON.parse(this.undoStack.pop()); this.dirty = true; } this.render(); },
    draw(mode) { this.mode = mode; this.points = []; this.notice = mode === 'hole' ? 'Click inside the area to outline an exclusion, then Finish.' : 'Click the map to place corners, then Finish.'; },
    finish() {
        if (this.points.length < 3) { this.error = 'Place at least three corners.'; return; }
        this.checkpoint();
        const ring = [...this.points, [...this.points[0]]];
        if (this.mode === 'hole' && this.form.geometry?.type === 'Polygon') this.form.geometry.coordinates.push(ring);
        else if (this.mode === 'part' && this.form.geometry) {
            const parts = this.form.geometry.type === 'MultiPolygon' ? this.form.geometry.coordinates : [this.form.geometry.coordinates];
            this.form.geometry = { type: 'MultiPolygon', coordinates: [...parts, [ring]] };
        } else this.form.geometry = { type: 'Polygon', coordinates: [ring] };
        this.form.shape.type = 'polygon'; this.mode = ''; this.points = []; this.notice = ''; this.error = ''; this.render();
    },
    generate() {
        this.checkpoint();
        const s = this.form.shape, radius = Number(s.radius), angle = s.type === 'circle' ? 360 : Number(s.angle), azimuth = Number(s.azimuth);
        if (!(radius >= 1 && radius <= 100000 && angle >= 1 && angle <= 360 && azimuth >= 0 && azimuth <= 360)) { this.error = 'Check radius (1–100,000 m), bearing and beam angle.'; return; }
        const lat = config.latitude * Math.PI / 180, lon = config.longitude * Math.PI / 180, distance = radius / 6371008.8;
        const ring = angle < 360 ? [[config.longitude, config.latitude]] : [];
        for (let i = 0; i <= 72; i++) {
            const bearing = (azimuth - angle / 2 + angle * i / 72) * Math.PI / 180;
            const phi = Math.asin(Math.sin(lat) * Math.cos(distance) + Math.cos(lat) * Math.sin(distance) * Math.cos(bearing));
            const lambda = lon + Math.atan2(Math.sin(bearing) * Math.sin(distance) * Math.cos(lat), Math.cos(distance) - Math.sin(lat) * Math.sin(phi));
            ring.push([lambda * 180 / Math.PI, phi * 180 / Math.PI]);
        }
        ring.push([...ring[0]]); this.form.geometry = { type: 'Polygon', coordinates: [ring] }; this.form.evidence = 'estimated'; this.mode = ''; this.error = ''; this.render();
    },
    render() {
        if (!this.layers) return;
        this.layers.clearLayers();
        this.areas.forEach(area => {
            const rev = area.revisions.find(r => r.id === area.published_revision_id);
            if (rev && area.id !== this.selected?.id) L.geoJSON(rev.snapshot.geometry, { style: { color: '#94a3b8', weight: 1, fillOpacity: .08 } }).addTo(this.layers);
        });
        if (this.form.geometry) {
            L.geoJSON(this.form.geometry, { style: { color: '#2563eb', weight: 2, fillOpacity: .18 } }).addTo(this.layers);
            const polygons = this.form.geometry.type === 'Polygon' ? [this.form.geometry.coordinates] : this.form.geometry.coordinates;
            polygons.forEach(rings => rings.forEach(ring => ring.slice(0, -1).forEach((point, index) => {
                const next = ring[index + 1];
                const midpoint = L.circleMarker([(point[1] + next[1]) / 2, (point[0] + next[0]) / 2], { radius: 4, color: '#2563eb', fillColor: 'white', fillOpacity: 1 }).addTo(this.layers);
                midpoint.on('click', e => { L.DomEvent.stopPropagation(e); this.checkpoint(); ring.splice(index + 1, 0, [(point[0] + next[0]) / 2, (point[1] + next[1]) / 2]); this.form.shape.type = 'polygon'; this.render(); });
                const marker = L.marker([point[1], point[0]], { draggable: true, icon: L.divIcon({ className: '', html: '<span style="display:block;width:12px;height:12px;background:white;border:2px solid #2563eb;border-radius:50%"></span>', iconSize: [12, 12] }) }).addTo(this.layers);
                marker.on('dragstart', () => this.checkpoint());
                marker.on('dragend', e => { const p = e.target.getLatLng(); ring[index] = [p.lng, p.lat]; ring[ring.length - 1] = [...ring[0]]; this.form.shape.type = 'polygon'; this.render(); });
                marker.on('contextmenu', () => { if (ring.length <= 4) return; this.checkpoint(); ring.splice(index, 1); ring[ring.length - 1] = [...ring[0]]; this.form.shape.type = 'polygon'; this.render(); });
            })));
        }
        if (this.points.length) L.polyline(this.points.map(p => [p[1], p[0]]), { dashArray: '5 5' }).addTo(this.layers);
    },
    importGeometry() {
        try {
            if (this.importText.length > 200000) throw Error('GeoJSON is too large (200 KB maximum).');
            const data = JSON.parse(this.importText), geometry = data.type === 'Feature' ? data.geometry : data;
            if (!['Polygon', 'MultiPolygon'].includes(geometry?.type)) throw Error('Use a Polygon or MultiPolygon GeoJSON geometry or Feature.');
            const layer = L.geoJSON(geometry), bounds = layer.getBounds();
            if (!bounds.isValid()) throw Error('No valid coordinates found.');
            this.checkpoint(); this.form.geometry = geometry; this.form.shape.type = 'polygon'; this.render(); this.map.fitBounds(bounds); this.error = ''; this.notice = 'Imported preview. Review the outline before saving.';
        } catch (e) { this.error = e.message; }
    },
    measurements() {
        if (!this.form.geometry) return 'No boundary drawn';
        const polygons = this.form.geometry.type === 'Polygon' ? [this.form.geometry.coordinates] : this.form.geometry.coordinates;
        let area = 0, perimeter = 0;
        const rad = Math.PI / 180, earth = 6371008.8;
        polygons.forEach(rings => rings.forEach((ring, index) => {
            let sum = 0;
            for (let i = 0; i < ring.length - 1; i++) {
                const [a,b] = ring[i], [c,d] = ring[i+1];
                sum += (c-a)*rad*(2+Math.sin(b*rad)+Math.sin(d*rad));
                const h = Math.sin((d-b)*rad/2)**2 + Math.cos(b*rad)*Math.cos(d*rad)*Math.sin((c-a)*rad/2)**2;
                perimeter += 2*earth*Math.asin(Math.sqrt(Math.min(1,h)));
            }
            area += (index ? -1 : 1)*Math.abs(sum*earth*earth/2);
        }));
        return `${(area/1000000).toFixed(2)} km² · ${(perimeter/1000).toFixed(2)} km boundary`;
    },
    exportGeometry() {
        if (!this.form.geometry) return;
        const url = URL.createObjectURL(new Blob([JSON.stringify({ type: 'Feature', geometry: this.form.geometry, properties: { name: this.form.name } }, null, 2)], { type: 'application/geo+json' }));
        const a = document.createElement('a'); a.href = url; a.download = 'coverage.geojson'; a.click(); URL.revokeObjectURL(url);
    },
    restore(revision) { if (!this.canLeave()) return; this.checkpoint(); this.form = JSON.parse(JSON.stringify(revision.snapshot)); this.form.status = 'draft'; this.render(); this.notice = 'Revision restored as an unsaved draft. Save to keep it.'; },
    async save(status) {
        if (this.busy) return;
        if (this.mode) { this.error = 'Finish or cancel drawing before saving.'; return; }
        this.busy = true; this.error = ''; this.notice = '';
        try {
            const response = await fetch(config.saveUrl + (this.selected ? '/' + this.selected.id : ''), { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf }, body: JSON.stringify({ ...this.form, status, version: this.selected?.version || 0, access_point_id: this.form.access_point_id || null, verified_on: this.form.verified_on || null }) });
            const data = await response.json();
            if (!response.ok) throw Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Unable to save. Try again.');
            this.areas = data.areas; this.dirty = false; this.open(this.areas.find(a => a.id === data.id)); this.notice = status === 'published' ? 'Coverage published.' : status === 'archived' ? 'Coverage archived.' : 'Draft saved. Published coverage stays in place.';
        } catch (e) { this.error = e.message; } finally { this.busy = false; }
    },
});
