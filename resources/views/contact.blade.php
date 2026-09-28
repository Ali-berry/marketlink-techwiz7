<x-layouts.public title="Contact">

    {{-- hero - home aur about jaisa photo + dark tint --}}
    <section class="relative isolate flex min-h-[320px] items-center overflow-hidden">
        <img src="{{ asset('images/site/contact-hero.webp') }}" alt="" class="absolute inset-0 h-full w-full object-cover">
        <div class="absolute inset-0 bg-black/35"></div>

        <div class="relative mx-auto max-w-3xl px-4 py-20 text-center sm:px-8">
            <h1 class="font-display text-4xl font-semibold text-white [text-shadow:0_2px_12px_rgba(0,0,0,0.25)]">Get in touch</h1>
            <p class="mx-auto mt-4 max-w-xl text-white/85 [text-shadow:0_2px_12px_rgba(0,0,0,0.25)]">Questions about an order, a stall, or joining as a farmer? Reach us directly.</p>
        </div>
    </section>

    <section class="py-16">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 sm:px-8 lg:grid-cols-2">
            <div class="space-y-4">
                <div class="glass flex items-start gap-4 p-6">
                    <span class="glass-icon-chip h-11 w-11 text-lg text-leaf-600">
                        <iconify-icon icon="tabler:mail"></iconify-icon>
                    </span>
                    <div>
                        <p class="font-medium text-soil">Email</p>
                        <a href="mailto:hello@marketlink.test" class="text-sm text-leaf-600 hover:text-leaf-700 hover:underline">hello@marketlink.test</a>
                    </div>
                </div>

                <div class="glass flex items-start gap-4 p-6">
                    <span class="glass-icon-chip h-11 w-11 text-lg text-tomato-600">
                        <iconify-icon icon="tabler:phone"></iconify-icon>
                    </span>
                    <div>
                        <p class="font-medium text-soil">Phone</p>
                        <a href="tel:{{ config('marketlink.office_location.phone_dial') }}" class="text-sm text-leaf-600 hover:text-leaf-700 hover:underline">{{ config('marketlink.office_location.phone') }}</a>
                        <p class="mt-0.5 text-xs text-soil-muted">Monday to Saturday, 9am to 6pm Central Time</p>
                    </div>
                </div>

                <div class="glass flex items-start gap-4 p-6">
                    <span class="glass-icon-chip h-11 w-11 text-lg text-soil">
                        <iconify-icon icon="tabler:map-pin"></iconify-icon>
                    </span>
                    <div>
                        <p class="font-medium text-soil">Office</p>
                        <p class="text-sm text-soil-muted">{{ config('marketlink.office_location.address') }}</p>
                    </div>
                </div>
            </div>

            <div class="glass h-[360px] overflow-hidden p-2 lg:h-auto lg:min-h-[360px]">
                <div class="h-full w-full overflow-hidden rounded-2xl"
                     data-leaflet-map
                     data-center-lat="{{ config('marketlink.office_location.latitude') }}"
                     data-center-lng="{{ config('marketlink.office_location.longitude') }}"
                     data-zoom="14"
                     data-markers="{{ json_encode($officeMapMarkers) }}"
                     role="img" aria-label="Map showing the MarketLink office location"></div>
            </div>
        </div>
    </section>

    {{-- FAQ - Buyer / Farmer toggle, dono ke sawal DOM mein hain, x-show bas badalta hai --}}
    <section class="py-16">
        <div class="mx-auto max-w-3xl px-4 sm:px-8" x-data="{ role: 'buyer', openIndex: null }">
            <div class="text-center">
                <h2 class="font-display text-2xl font-semibold">Frequently asked questions</h2>
                <p class="mt-2 text-soil-muted">Pick who you are and find your answer below.</p>
            </div>

            <div class="glass mx-auto mt-8 flex w-fit gap-1 !rounded-full p-1.5">
                <button type="button" @click="role = 'buyer'; openIndex = null"
                        class="rounded-full px-5 py-2 text-sm font-medium transition"
                        :class="role === 'buyer' ? 'btn-glass-orange' : 'text-soil-muted hover:text-soil'">
                    I'm a Buyer
                </button>
                <button type="button" @click="role = 'farmer'; openIndex = null"
                        class="rounded-full px-5 py-2 text-sm font-medium transition"
                        :class="role === 'farmer' ? 'btn-glass-orange' : 'text-soil-muted hover:text-soil'">
                    I'm a Farmer
                </button>
            </div>

            <div class="mt-8 space-y-3" x-show="role === 'buyer'">
                @foreach ($buyerFaqs as $i => $faq)
                    <div class="glass overflow-hidden">
                        <button type="button" @click="openIndex = openIndex === 'buyer-{{ $i }}' ? null : 'buyer-{{ $i }}'"
                                class="flex w-full items-center justify-between gap-4 p-5 text-left font-medium text-soil">
                            {{ $faq['question'] }}
                            <iconify-icon icon="tabler:chevron-down" class="shrink-0 text-lg text-tomato-600 transition"
                                          :class="openIndex === 'buyer-{{ $i }}' ? 'rotate-180' : ''"></iconify-icon>
                        </button>
                        <div x-show="openIndex === 'buyer-{{ $i }}'" class="px-5 pb-5 text-sm text-soil-muted">
                            {{ $faq['answer'] }}
                        </div>
                    </div>
                @endforeach

                <p class="mt-6 text-center text-sm text-soil-muted">
                    Still have a question? Call us at
                    <a href="tel:{{ config('marketlink.office_location.phone_dial') }}" class="font-medium text-leaf-600 hover:text-leaf-700 hover:underline">{{ config('marketlink.office_location.phone') }}</a>
                    (Mon-Sat, 9am-6pm) or email
                    <a href="mailto:hello@marketlink.test" class="font-medium text-leaf-600 hover:text-leaf-700 hover:underline">hello@marketlink.test</a>.
                </p>
            </div>

            <div class="mt-8 space-y-3" x-show="role === 'farmer'" x-cloak>
                @foreach ($farmerFaqs as $i => $faq)
                    <div class="glass overflow-hidden">
                        <button type="button" @click="openIndex = openIndex === 'farmer-{{ $i }}' ? null : 'farmer-{{ $i }}'"
                                class="flex w-full items-center justify-between gap-4 p-5 text-left font-medium text-soil">
                            {{ $faq['question'] }}
                            <iconify-icon icon="tabler:chevron-down" class="shrink-0 text-lg text-tomato-600 transition"
                                          :class="openIndex === 'farmer-{{ $i }}' ? 'rotate-180' : ''"></iconify-icon>
                        </button>
                        <div x-show="openIndex === 'farmer-{{ $i }}'" class="px-5 pb-5 text-sm text-soil-muted">
                            {{ $faq['answer'] }}
                        </div>
                    </div>
                @endforeach

                <p class="mt-6 text-center text-sm text-soil-muted">
                    Want to join or need help with your stall? Call our farmer support line at
                    <a href="tel:{{ config('marketlink.office_location.phone_dial') }}" class="font-medium text-leaf-600 hover:text-leaf-700 hover:underline">{{ config('marketlink.office_location.phone') }}</a>
                    or email
                    <a href="mailto:farmers@marketlink.test" class="font-medium text-leaf-600 hover:text-leaf-700 hover:underline">farmers@marketlink.test</a>.
                </p>
            </div>
        </div>
    </section>

    @push('scripts')
        @vite('resources/js/market-map.js')
    @endpush

</x-layouts.public>
