// stall profile form pe draggable pin, taake farmer coordinates haath se na likhe. market-map.js wala Leaflet setup
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

import markerIconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import markerIconUrl from 'leaflet/dist/images/marker-icon.png';
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({ iconRetinaUrl: markerIconRetinaUrl, iconUrl: markerIconUrl, shadowUrl: markerShadowUrl });

const mapElement = document.querySelector('[data-stall-location-map]');

if (mapElement) {
    const startLatitude = parseFloat(mapElement.dataset.latitude);
    const startLongitude = parseFloat(mapElement.dataset.longitude);
    const zoomLevel = parseInt(mapElement.dataset.zoom ?? '13', 10);

    const latitudeInput = document.querySelector(mapElement.dataset.latitudeInput);
    const longitudeInput = document.querySelector(mapElement.dataset.longitudeInput);

    const map = L.map(mapElement).setView([startLatitude, startLongitude], zoomLevel);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    const pin = L.marker([startLatitude, startLongitude], { draggable: true }).addTo(map);

    const writeCoordinatesToInputs = (position) => {
        latitudeInput.value = position.lat.toFixed(6);
        longitudeInput.value = position.lng.toFixed(6);
    };

    pin.on('dragend', () => writeCoordinatesToInputs(pin.getLatLng()));

    // map pe tap se bhi pin hilta hai - phone pe drag mushkil hai
    map.on('click', (event) => {
        pin.setLatLng(event.latlng);
        writeCoordinatesToInputs(event.latlng);
    });

    // market-map.js wala fix: form settle ho raha ho to Leaflet galat size ke tiles load karta hai (grey map).
    // size badalne pe dobara measure karo aur pin center mein rakho, jab tak farmer khud map na hilaye
    let userHasMovedMap = false;
    map.on('dragstart', () => { userHasMovedMap = true; });
    mapElement.addEventListener('wheel', () => { userHasMovedMap = true; }, { passive: true });

    const refitAfterResize = () => {
        map.invalidateSize();
        if (! userHasMovedMap) {
            map.setView(pin.getLatLng(), map.getZoom());
        }
    };

    new ResizeObserver(refitAfterResize).observe(mapElement);
    window.addEventListener('load', refitAfterResize, { once: true });
}
