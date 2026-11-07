import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const canvas = document.querySelector('#map');
const status = document.querySelector('#status');
const map = L.map(canvas, { scrollWheelZoom: false }).setView([0, 0], 2);
L.tileLayer(canvas.dataset.tiles, { attribution: canvas.dataset.attribution, maxZoom: 19 }).addTo(map);

async function loadCoverage() {
    try {
        const response = await fetch(canvas.dataset.feed, {credentials: 'omit'});
        if (!response.ok) throw new Error();
        const coverage = L.geoJSON(await response.json(), {
            onEachFeature(feature, layer) {
                const label = document.createElement('span');
                label.textContent = [feature.properties.name, feature.properties.availability?.replaceAll('_', ' ')].filter(Boolean).join(' · ');
                layer.bindPopup(label);
            },
        }).addTo(map);
        if (coverage.getBounds().isValid()) {
            map.fitBounds(coverage.getBounds(), {padding: [12,12], maxZoom: 16});
            status.hidden = true;
        } else {
            status.textContent = 'No coverage areas have been published yet.';
        }
    } catch {
        status.textContent = 'Coverage could not be loaded. Reload the map to try again.';
    }
}
loadCoverage();
