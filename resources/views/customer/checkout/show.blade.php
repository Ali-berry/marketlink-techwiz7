@php
    $canPlaceOrder = $selectedMarket && $pickupOptions->isNotEmpty();
@endphp

<x-layouts.panel title="Checkout">

    <div class="mb-6">
        <a href="{{ route('customer.cart.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-soil-muted hover:text-soil">
            <iconify-icon icon="tabler:arrow-left"></iconify-icon>
            Back to basket
        </a>
    </div>

    <h2 class="text-2xl font-semibold">Checkout with {{ $farmer->stall_name }}</h2>
    <p class="mt-1 text-sm text-soil-muted">One order, paid and collected at the stall on pickup day.</p>

    <div class="mt-6 grid gap-8 lg:grid-cols-3 lg:items-start">
        <div class="space-y-6 lg:col-span-2">

            <section class="card p-5">
                <h3 class="flex items-center gap-3 font-semibold">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-leaf-600 text-sm font-semibold text-white">1</span>
                    Choose a market
                </h3>

                @if ($availableMarkets->isEmpty())
                    <p class="mt-3 text-sm text-soil-muted">This farmer has no pickup slots set up yet.</p>
                @else
                    <form method="GET" action="{{ route('customer.checkout.show', $farmer) }}" class="mt-4 max-w-sm">
                        <label class="sr-only" for="market_id">Market</label>
                        <select id="market_id" name="market_id" class="form-input" onchange="this.form.submit()">
                            <option value="">Choose a market...</option>
                            @foreach ($availableMarkets as $market)
                                <option value="{{ $market->id }}" @selected($selectedMarket?->id === $market->id)>{{ $market->name }}</option>
                            @endforeach
                        </select>
                        <noscript><button type="submit" class="btn-outline mt-2">Go</button></noscript>
                    </form>
                @endif
            </section>

            {{-- step 2 aur 3 ek hi form hain, "Place order" button right wali summary mein form="checkout-form" se --}}
            <form id="checkout-form" method="POST" action="{{ route('customer.checkout.store', $farmer) }}" class="space-y-6">
                @csrf
                @if ($selectedMarket)
                    <input type="hidden" name="market_id" value="{{ $selectedMarket->id }}">
                @endif

                <section @class(['card p-5', 'opacity-60' => ! $selectedMarket])>
                    <h3 class="flex items-center gap-3 font-semibold">
                        <span @class([
                            'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                            'bg-leaf-600 text-white' => $selectedMarket,
                            'bg-white/70 text-soil-muted' => ! $selectedMarket,
                        ])>2</span>
                        Choose a pickup slot
                    </h3>

                    @if (! $selectedMarket)
                        <p class="mt-3 text-sm text-soil-muted">Pick a market first to see its pickup slots.</p>
                    @elseif ($pickupOptions->isEmpty())
                        <p class="mt-3 text-sm text-soil-muted">No pickup slots are available at this market in the next few weeks. Try another market.</p>
                    @else
                        {{-- har tile asal (chhupa hua) radio hai, taake keyboard aur "required" kaam karen --}}
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            @foreach ($pickupOptions as $option)
                                @php $choiceValue = $option['pickup_window_id'].'_'.$option['date']->toDateString(); @endphp
                                <label class="group flex cursor-pointer flex-col gap-1 rounded-2xl border-2 border-white/70 bg-white/50 p-4 transition hover:bg-white/80
                                              has-[:checked]:border-tomato-400 has-[:checked]:bg-white/90 has-[:checked]:shadow-md has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-tomato-300">
                                    <input type="radio" name="pickup_choice" value="{{ $choiceValue }}" class="sr-only"
                                           @checked(old('pickup_choice') === $choiceValue) required>
                                    <span class="flex items-center justify-between gap-2">
                                        <span class="font-display font-semibold text-soil">{{ $option['date']->format('D j M') }}</span>
                                        <iconify-icon icon="tabler:circle-check-filled" class="text-xl text-tomato-500 opacity-0 transition group-has-[:checked]:opacity-100"></iconify-icon>
                                    </span>
                                    <span class="text-sm text-soil">{{ $option['window']->timeRangeText() }} {{ $selectedMarket->timezoneLabel() }}</span>
                                    <span class="text-xs text-soil-muted">{{ $option['spots_left'] }} {{ \Illuminate\Support\Str::plural('spot', $option['spots_left']) }} left</span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('pickup_choice')" class="mt-2" />
                    @endif
                </section>

                <section @class(['card p-5', 'opacity-60' => ! $canPlaceOrder])>
                    <h3 class="flex items-center gap-3 font-semibold">
                        <span @class([
                            'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                            'bg-leaf-600 text-white' => $canPlaceOrder,
                            'bg-white/70 text-soil-muted' => ! $canPlaceOrder,
                        ])>3</span>
                        Add a note <span class="font-normal text-soil-muted">(optional)</span>
                    </h3>
                    <label class="sr-only" for="customer_note">Note for the farmer</label>
                    <textarea id="customer_note" name="customer_note" rows="2" class="form-input mt-4"
                              placeholder="Anything the farmer should know?" @disabled(! $canPlaceOrder)>{{ old('customer_note') }}</textarea>
                    <x-input-error :messages="$errors->get('customer_note')" class="mt-1.5" />
                    <x-input-error :messages="$errors->get('market_id')" class="mt-1.5" />
                </section>
            </form>
        </div>

        {{-- lg:top-28 - sticky header ke bilkul neeche --}}
        <aside class="card p-5 lg:sticky lg:top-28">
            <h3 class="font-semibold">Order summary</h3>
            <div class="mt-4 space-y-2">
                @foreach ($basketGroup['items'] as $line)
                    <div class="flex justify-between gap-3 text-sm">
                        <span class="text-soil-muted">{{ $line['quantity'] }} &times; {{ $line['product']->name }}</span>
                        <x-ui.money :amount="$line['lineTotal']" />
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex items-end justify-between border-t border-white/60 pt-4">
                <span class="font-medium">Total</span>
                <x-ui.money :amount="$basketGroup['subtotal']" class="font-display text-3xl font-semibold text-tomato-600" />
            </div>

            <button type="submit" form="checkout-form" class="btn-glass-orange mt-5 w-full py-3 text-base disabled:cursor-not-allowed disabled:opacity-50"
                    @disabled(! $canPlaceOrder)>
                <iconify-icon icon="tabler:check"></iconify-icon>
                Place order
            </button>
            @unless ($canPlaceOrder)
                <p class="mt-2 text-center text-xs text-soil-muted">Choose a market and a pickup slot first.</p>
            @endunless

            <p class="mt-4 flex items-center justify-center gap-1.5 text-xs text-soil-muted">
                <iconify-icon icon="tabler:cash" class="text-leaf-600"></iconify-icon>
                Pay at the stall on pickup day
            </p>
        </aside>
    </div>

</x-layouts.panel>
