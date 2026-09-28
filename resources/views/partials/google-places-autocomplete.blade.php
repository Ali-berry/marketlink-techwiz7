@php
    // chahiye: inputId, inputName, latInputName, lngInputName, optional placeholder aur types (default area level).
    // GOOGLE_MAPS_API_KEY na ho to normal text input - na script, na console error
    $googleMapsKey = config('services.google.maps_key');
    $placeTypes = $types ?? '(regions)';

    // har input ka alag naam, taake page pe do baar include ho to Google callbacks na takraen
    $callbackName = 'initPlacesAutocomplete_'.str_replace('-', '_', $inputId);
@endphp

<input type="text" id="{{ $inputId }}" name="{{ $inputName }}" value="{{ old($inputName) }}"
       placeholder="{{ $placeholder ?? 'Start typing...' }}" class="form-input" autocomplete="off">
<input type="hidden" id="{{ $inputId }}-latitude" name="{{ $latInputName }}" value="{{ old($latInputName) }}">
<input type="hidden" id="{{ $inputId }}-longitude" name="{{ $lngInputName }}" value="{{ old($lngInputName) }}">

@if ($googleMapsKey)
    @push('scripts')
        <script>
            function {{ $callbackName }}() {
                const areaInput = document.getElementById('{{ $inputId }}');
                if (! areaInput || typeof google === 'undefined') return;

                // componentRestrictions jaan boojh ke nahi - kisi bhi shehar ya mulk ke liye chalna chahiye
                const autocomplete = new google.maps.places.Autocomplete(areaInput, {
                    types: ['{{ $placeTypes }}'],
                });

                autocomplete.addListener('place_changed', () => {
                    const place = autocomplete.getPlace();
                    if (! place.geometry) return;

                    document.getElementById('{{ $inputId }}-latitude').value = place.geometry.location.lat();
                    document.getElementById('{{ $inputId }}-longitude').value = place.geometry.location.lng();
                });
            }
        </script>
        <script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&libraries=places&callback={{ $callbackName }}" async defer></script>
    @endpush
@endif
