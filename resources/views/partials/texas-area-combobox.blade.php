@php
    // Google Places key aane tak Area/Location ka fallback - wahi hidden lat / long inputs, bas Texas ki fixed list se.
    // dark signup card aur light markets page dono pe:
    // - onLightBackground: dark green ki jagah light list
    // - initialValue: pehle se bhara (markets page ki saved location)
    // - noMatchMessage: signup free text leta hai, markets page ko list wala area chahiye
    // - area chunne pe "area-selected" event {name, latitude, longitude}, aur "area-field-reset" field khali karta hai
    $areas = \App\Data\TexasAreas::all();
    $onLightBackground = $onLightBackground ?? false;
    $dropdownClasses = $onLightBackground
        ? 'border-cream-dark bg-white/95 text-soil'
        : 'border-white/15 bg-leaf-900/95 text-white';
    $optionHoverClasses = $onLightBackground ? 'hover:bg-leaf-50 focus-visible:bg-leaf-50' : 'hover:bg-white/10 focus-visible:bg-white/10';
@endphp

<div x-data="{
        query: @js($initialValue ?? old($inputName, '')),
        open: false,
        areas: @js($areas),
        get filteredAreas() {
            const search = this.query.trim().toLowerCase();
            if (! search) return this.areas;
            return this.areas.filter((area) => area.name.toLowerCase().includes(search));
        },
        selectArea(area) {
            this.query = area.name;
            this.open = false;
            document.getElementById('{{ $inputId }}-latitude').value = area.latitude;
            document.getElementById('{{ $inputId }}-longitude').value = area.longitude;
            $dispatch('area-selected', area);
        },
     }"
     @area-field-reset.window="query = ''"
     class="relative">

    <input type="text" id="{{ $inputId }}" name="{{ $inputName }}"
           x-model="query" @focus="open = true" @input="open = true"
           @keydown.escape="open = false" @click.outside="open = false"
           placeholder="{{ $placeholder ?? 'Type or pick a city/area...' }}"
           class="form-input" autocomplete="off">

    <input type="hidden" id="{{ $inputId }}-latitude" name="{{ $latInputName }}" value="{{ old($latInputName) }}">
    <input type="hidden" id="{{ $inputId }}-longitude" name="{{ $lngInputName }}" value="{{ old($lngInputName) }}">

    {{-- dark auth card pe list dark green panel, light page pe normal light panel --}}
    <div x-show="open && filteredAreas.length" x-cloak
         class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border shadow-lg backdrop-blur {{ $dropdownClasses }}">
        <template x-for="area in filteredAreas" :key="area.name">
            <button type="button" @click="selectArea(area)"
                    class="block w-full px-4 py-2 text-left text-sm focus-visible:outline-none {{ $optionHoverClasses }}" x-text="area.name"></button>
        </template>
    </div>

    <p x-show="open && query.trim() && ! filteredAreas.length" x-cloak
       class="absolute z-20 mt-1 w-full rounded-xl border px-4 py-2 text-sm opacity-90 shadow-lg backdrop-blur {{ $dropdownClasses }}">
        {{ $noMatchMessage ?? 'No matching area - you can still type your own.' }}
    </p>
</div>
