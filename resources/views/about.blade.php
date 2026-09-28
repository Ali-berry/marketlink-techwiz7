<x-layouts.public title="About">

    {{-- hero - photo ke upar halka dark tint, white heading pe text-shadow taake parha jaye --}}
    <section class="relative isolate flex min-h-[420px] items-center overflow-hidden">
        <img src="{{ asset('images/site/about-farm-field.webp') }}" alt="" class="absolute inset-0 h-full w-full object-cover">
        <div class="absolute inset-0 bg-black/45"></div>

        <div class="relative mx-auto max-w-3xl px-4 py-20 text-center sm:px-8">
            <h1 class="font-display text-4xl font-semibold text-white [text-shadow:0_2px_12px_rgba(0,0,0,0.25)] sm:text-5xl">
                Built to close the gap between farm and market day
            </h1>
            <p class="mx-auto mt-4 max-w-xl text-white/85 [text-shadow:0_2px_12px_rgba(0,0,0,0.25)]">
                MarketLink lets customers reserve fresh produce ahead of time and lets farmers know exactly
                what to bring, so nothing is guessed and nothing goes to waste.
            </p>
        </div>
    </section>

    {{-- text ke saath ek photo, glass frame mein --}}
    <section class="py-16">
        <div class="mx-auto grid max-w-5xl gap-10 px-4 sm:px-8 lg:grid-cols-2 lg:items-center">
            <div class="glass overflow-hidden p-2">
                <img src="{{ asset('images/site/about-packing-crate.webp') }}" alt="A farmer packing tomatoes, carrots and kale into a crate for a pre-order"
                     class="h-72 w-full rounded-2xl object-cover sm:h-[26rem]">
            </div>

            <div class="space-y-8">
                <div>
                    <h2 class="font-display text-2xl font-semibold">Why we built this</h2>
                    <p class="mt-3 text-soil-muted">
                        Farmers markets run on guesswork: farmers bring what they think will sell, and customers
                        show up hoping their favourite stall still has stock. MarketLink replaces that guesswork
                        with pre-orders, so farmers pack the right amount and customers know before they leave home.
                    </p>
                </div>
                <div>
                    <h2 class="font-display text-2xl font-semibold">How we work</h2>
                    <p class="mt-3 text-soil-muted">
                        Every farmer on MarketLink is reviewed and approved by our admin team before their stall
                        goes live. Customers pre-order and pick a pickup slot, then pay the farmer directly at the
                        stall on market day, just like they always have.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- har value ki ek photo, upar wala hi glass frame --}}
    <section class="py-16">
        <div class="mx-auto max-w-5xl px-4 text-center sm:px-8">
            <h2 class="font-display text-2xl font-semibold">What we stand for</h2>

            <div class="mt-8 grid gap-6 sm:grid-cols-3">
                <div class="glass p-2 pb-6 transition hover:-translate-y-1.5 hover:shadow-xl">
                    <img src="{{ asset('images/site/about-local-first.webp') }}" alt="Two neighbours laughing together while shopping at their local market"
                         class="aspect-[4/3] w-full rounded-2xl object-cover">
                    <p class="mt-4 px-4 font-medium text-soil">Local first</p>
                    <p class="mt-1 px-4 text-sm text-soil-muted">Every stall on MarketLink sells at a real, physical market near you.</p>
                </div>
                <div class="glass p-2 pb-6 transition hover:-translate-y-1.5 hover:shadow-xl">
                    <img src="{{ asset('images/site/about-farmers-we-vet.webp') }}" alt="A farmer checking each tomato before it goes into the crate"
                         class="aspect-[4/3] w-full rounded-2xl object-cover">
                    <p class="mt-4 px-4 font-medium text-soil">Farmers we vet</p>
                    <p class="mt-1 px-4 text-sm text-soil-muted">Our admin team approves every farmer before customers can order from them.</p>
                </div>
                <div class="glass p-2 pb-6 transition hover:-translate-y-1.5 hover:shadow-xl">
                    <img src="{{ asset('images/site/about-no-wasted-trips.webp') }}" alt="A farmer handing a customer a bag of produce reserved ahead"
                         class="aspect-[4/3] w-full rounded-2xl object-cover">
                    <p class="mt-4 px-4 font-medium text-soil">No wasted trips</p>
                    <p class="mt-1 px-4 text-sm text-soil-muted">Reserve ahead so you're not left choosing from whatever's left.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 text-center">
        <a href="{{ route('contact') }}" class="btn-glass-orange px-6 py-3 text-base">Get in touch</a>
    </section>

</x-layouts.public>
