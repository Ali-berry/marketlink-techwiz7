// Leaflet map - markets page, market detail aur contact page pe. Sirf map wale pages load karte hain
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Vite leaflet ki marker images ka naam hash kar deta hai, is liye icon paths khud dene parte hain
import markerIconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import markerIconUrl from 'leaflet/dist/images/marker-icon.png';
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({ iconRetinaUrl: markerIconRetinaUrl, iconUrl: markerIconUrl, shadowUrl: markerShadowUrl });

// kai pins frame karte waqt zoom range - 5 pe poora Texas, 13 pe ek mohalla
const MIN_FIT_ZOOM = 5;
const MAX_FIT_ZOOM = 13;
const FIT_PADDING_PX = 30;

// "You are here" har market se itna door ho (jaise doosre mulk se) to framing mein shamil nahi, warna aadhi duniya dikhti
const MAX_STARTING_POINT_DISTANCE_METERS = 800 * 1000;

// har [data-leaflet-map] apna map banta hai, markers data attribute se
document.querySelectorAll('[data-leaflet-map]').forEach((mapElement) => {
    const centerLatitude = parseFloat(mapElement.dataset.centerLat);
    const centerLongitude = parseFloat(mapElement.dataset.centerLng);
    const zoomLevel = parseInt(mapElement.dataset.zoom ?? '12', 10);
    // bina coordinates wale bounds kharab karte, yahan hata do
    const markers = JSON.parse(mapElement.dataset.markers ?? '[]')
        .map((marker) => ({ ...marker, lat: parseFloat(marker.lat), lng: parseFloat(marker.lng) }))
        .filter((marker) => Number.isFinite(marker.lat) && Number.isFinite(marker.lng));

    const map = L.map(mapElement).setView([centerLatitude, centerLongitude], zoomLevel);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    // market id se keyed, taake card aur marker highlight ke liye JSON dobara na parhna pare
    const leafletMarkerById = new Map();

    markers.forEach((marker) => {
        const popupText = marker.details ? `<strong>${marker.name}</strong><br>${marker.details}` : `<strong>${marker.name}</strong>`;
        const leafletMarker = L.marker([marker.lat, marker.lng]).addTo(map).bindPopup(popupText);
        leafletMarkerById.set(marker.id, leafletMarker);
    });

    // markets page pe jab visitor apni jagah chunta hai (neeche dekho)
    let startingPoint = null;

    // markets poori state mein hain, fixed center / zoom kaam nahi karta - jo pins mile unhe frame karo,
    // "You are here" bhi. Ek pin ho to page ka apna zoom, koi pin na ho to upar wala fallback
    const frameMarkers = () => {
        const mapSize = map.getSize();

        // jis box ka size abhi bana nahi us pe fitBounds ajeeb zoom deta hai aur map Caribbean chala jata hai.
        // size milne pe neeche wala resize observer dobara call karta hai
        if (mapSize.x <= FIT_PADDING_PX * 2 || mapSize.y <= FIT_PADDING_PX * 2) {
            return;
        }

        if (markers.length > 1) {
            const bounds = L.latLngBounds(markers.map((marker) => [marker.lat, marker.lng]));

            if (startingPoint && startingPointIsNearAMarket()) {
                bounds.extend(startingPoint);
            }

            // animation nahi - zoom animation ke beech resize aaye to map galat jagah chala jata hai
            map.fitBounds(bounds, { padding: [FIT_PADDING_PX, FIT_PADDING_PX], maxZoom: MAX_FIT_ZOOM, animate: false });

            if (map.getZoom() < MIN_FIT_ZOOM) {
                map.setView(bounds.getCenter(), MIN_FIT_ZOOM, { animate: false });
            }
        } else if (markers.length === 1) {
            map.setView([markers[0].lat, markers[0].lng], zoomLevel, { animate: false });
        }
    };

    function startingPointIsNearAMarket() {
        return markers.some((marker) => startingPoint.distanceTo([marker.lat, marker.lng]) <= MAX_STARTING_POINT_DISTANCE_METERS);
    }

    frameMarkers();

    // fitBounds us waqt ke box size se zoom nikalta hai - box abhi settle ho raha ho to galat zoom.
    // is liye size badalne pe dobara frame karo, jab tak visitor khud map na hilaye
    let visitorHasMovedMap = false;
    map.on('dragstart', () => { visitorHasMovedMap = true; });
    mapElement.addEventListener('wheel', () => { visitorHasMovedMap = true; }, { passive: true });
    map.zoomControl?.getContainer().addEventListener('click', () => { visitorHasMovedMap = true; });

    const reframeAfterResize = () => {
        map.invalidateSize({ animate: false });
        if (! visitorHasMovedMap) {
            frameMarkers();
        }
    };

    new ResizeObserver(reframeAfterResize).observe(mapElement);
    window.addEventListener('load', reframeAfterResize, { once: true });

    // sirf markets page: markets-distance-sort.js batata hai visitor kahan hai, wahan orange "You are here" dot
    if (mapElement.hasAttribute('data-follows-starting-point')) {
        let startingPointDot = null;

        window.addEventListener('markets:starting-point-changed', (event) => {
            startingPoint = L.latLng(event.detail.latitude, event.detail.longitude);

            startingPointDot?.remove();
            startingPointDot = L.circleMarker(startingPoint, {
                radius: 9,
                color: '#ffffff',
                weight: 3,
                fillColor: '#E0662F', // tomato-500, taake market pin na lage
                fillOpacity: 1,
            })
                .addTo(map)
                .bindTooltip('You are here', { permanent: true, direction: 'top', offset: [0, -10] });

            visitorHasMovedMap = false;
            frameMarkers();
        });

        window.addEventListener('markets:starting-point-cleared', () => {
            startingPointDot?.remove();
            startingPointDot = null;
            startingPoint = null;
            visitorHasMovedMap = false;
            frameMarkers();
        });
    }

    // [data-market-card] sirf markets page pe hain, baqi jagah ye kuch nahi karta
    document.querySelectorAll('[data-market-card]').forEach((card) => {
        const marketId = Number(card.dataset.marketId);
        const leafletMarker = leafletMarkerById.get(marketId);
        if (! leafletMarker) return;

        // alag pin button - card itna bhara hua hai ke khali jagah click karna mushkil tha
        card.querySelector('[data-locate-on-map]')?.addEventListener('click', () => {
            visitorHasMovedMap = true;
            map.flyTo(leafletMarker.getLatLng(), Math.max(map.getZoom(), 14), { duration: 0.6 });
            leafletMarker.openPopup();
            document.querySelector('[data-leaflet-map]')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });

        leafletMarker.on('click', () => {
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            card.classList.add('ring-2', 'ring-leaf-400');
            setTimeout(() => card.classList.remove('ring-2', 'ring-leaf-400'), 1600);
        });
    });
});
