// markets page: starting point se qareeb wali market pehle aur har card pe "X mi away".
// starting point teen jagah se: "Find markets near" field, "Use my location", ya customer ki saved location -
// teeno showMarketsNearest() se, jo aakhir mein use hua wahi dikhta hai. market-map.js bhi isi point pe marker lagata hai
const EARTH_RADIUS_MILES = 3958.8;

// App\Helpers\DistanceHelper::distanceInMiles() wala hi haversine
function distanceInMiles(lat1, lng1, lat2, lng2) {
    const toRadians = (degrees) => (degrees * Math.PI) / 180;

    const deltaLat = toRadians(lat2 - lat1);
    const deltaLng = toRadians(lng2 - lng1);

    const a = Math.sin(deltaLat / 2) ** 2
        + Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2)) * Math.sin(deltaLng / 2) ** 2;

    return EARTH_RADIUS_MILES * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

const startingPointBar = document.querySelector('[data-markets-starting-point]');
const cardsContainer = document.querySelector('[data-market-cards]');

if (startingPointBar && cardsContainer) {
    const locationButton = startingPointBar.querySelector('[data-use-my-location]');
    const clearButton = startingPointBar.querySelector('[data-clear-starting-point]');
    const startingPointNote = startingPointBar.querySelector('[data-starting-point-note]');
    const startingPointLabel = startingPointBar.querySelector('[data-starting-point-label]');
    const locationButtonText = locationButton.innerHTML;
    const marketCards = [...cardsContainer.querySelectorAll('[data-market-card]')];

    function showMarketsNearest(latitude, longitude, placeName) {
        marketCards.forEach((card) => {
            const milesAway = distanceInMiles(latitude, longitude, parseFloat(card.dataset.lat), parseFloat(card.dataset.lng));
            card.dataset.milesAway = milesAway;

            const distanceBadge = card.querySelector('[data-distance-label]');
            distanceBadge.textContent = `${milesAway.toFixed(1)} mi away`;
            distanceBadge.classList.remove('hidden');
        });

        [...marketCards]
            .sort((firstCard, secondCard) => firstCard.dataset.milesAway - secondCard.dataset.milesAway)
            .forEach((card) => cardsContainer.appendChild(card));

        startingPointLabel.textContent = placeName;
        startingPointNote.hidden = false;
        clearButton.hidden = false;

        window.dispatchEvent(new CustomEvent('markets:starting-point-changed', {
            detail: { latitude, longitude, placeName },
        }));
    }

    // guest jaisa: A-Z, na distance na marker
    function clearStartingPoint() {
        [...marketCards]
            .sort((firstCard, secondCard) => firstCard.dataset.marketName.localeCompare(secondCard.dataset.marketName))
            .forEach((card) => {
                card.querySelector('[data-distance-label]').classList.add('hidden');
                cardsContainer.appendChild(card);
            });

        startingPointNote.hidden = true;
        clearButton.hidden = true;
        locationButton.innerHTML = locationButtonText;

        window.dispatchEvent(new CustomEvent('area-field-reset'));
        window.dispatchEvent(new CustomEvent('markets:starting-point-cleared'));
    }

    // texas-area-combobox se area chuna gaya
    startingPointBar.addEventListener('area-selected', (event) => {
        locationButton.innerHTML = locationButtonText;
        showMarketsNearest(event.detail.latitude, event.detail.longitude, event.detail.name);
    });

    locationButton.addEventListener('click', () => {
        if (! navigator.geolocation) {
            locationButton.textContent = "Your browser can't share your location";
            return;
        }

        locationButton.disabled = true;
        locationButton.textContent = 'Finding you...';

        navigator.geolocation.getCurrentPosition(
            (position) => {
                locationButton.disabled = false;
                locationButton.innerHTML = locationButtonText;
                // likha hua area ab nahi, live location use hogi
                window.dispatchEvent(new CustomEvent('area-field-reset'));
                showMarketsNearest(position.coords.latitude, position.coords.longitude, 'your current location');
            },
            () => {
                locationButton.disabled = false;
                locationButton.textContent = "Couldn't get your location - check permissions";
            },
        );
    });

    clearButton.addEventListener('click', clearStartingPoint);

    // logged in customer ki saved location (MarketController) se page shuru
    if (startingPointBar.dataset.initialLatitude) {
        showMarketsNearest(
            parseFloat(startingPointBar.dataset.initialLatitude),
            parseFloat(startingPointBar.dataset.initialLongitude),
            startingPointBar.dataset.initialLabel,
        );
    }
}
