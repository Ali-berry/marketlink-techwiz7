<x-layouts.panel :title="$farmer->stall_name">

    <div class="mb-6">
        <a href="{{ route('admin.farmers.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-soil-muted hover:text-soil">
            <iconify-icon icon="tabler:arrow-left"></iconify-icon>
            Back to farmers
        </a>
    </div>

    {{-- details ke saath square photo - farmer ki photos square hain, chaure banner pe blur ho jati thin --}}
    <section class="card flex flex-col gap-6 md:flex-row md:items-start">
        <div class="mx-auto w-full max-w-[240px] shrink-0 md:mx-0 md:w-48">
            <img src="{{ $farmer->coverImageUrl() }}" alt="{{ $farmer->stall_name }}"
                 class="aspect-square w-full rounded-2xl border border-white/60 object-cover shadow-md">
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="font-display text-2xl font-semibold">{{ $farmer->stall_name }}</h1>
                        <x-ui.availability-badge :availability="$farmer->approval_status" />
                    </div>
                    <p class="mt-1 text-sm text-soil-muted">{{ $farmer->contact_person }} &middot; {{ $farmer->user->email }} &middot; {{ $farmer->user->phone }}</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    @if ($farmer->approval_status !== \App\Enums\FarmerApprovalStatus::Approved)
                        <form method="POST" action="{{ route('admin.farmers.approve', $farmer) }}">
                            @csrf
                            <button type="submit" class="btn-primary">
                                <iconify-icon icon="tabler:check"></iconify-icon>
                                {{ $farmer->approval_status === \App\Enums\FarmerApprovalStatus::Suspended ? 'Re-approve' : 'Approve' }}
                            </button>
                        </form>
                    @endif

                    @if ($farmer->approval_status !== \App\Enums\FarmerApprovalStatus::Suspended)
                        <x-ui.confirm-dialog title="Suspend this farmer?"
                                              body="Their products and stall will be hidden from customers immediately."
                                              :action="route('admin.farmers.suspend', $farmer)" confirm-label="Suspend farmer">
                            <x-slot:trigger>
                                <span class="btn-danger cursor-pointer">Suspend</span>
                            </x-slot:trigger>
                            <x-slot:fields>
                                <label class="form-label" for="reason">Reason (shown to the farmer)</label>
                                <textarea id="reason" name="reason" rows="2" class="form-input" required></textarea>
                            </x-slot:fields>
                        </x-ui.confirm-dialog>
                    @endif
                </div>
            </div>

            @if ($farmer->approval_status === \App\Enums\FarmerApprovalStatus::Suspended && $farmer->suspension_reason)
                <div class="mt-4 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <span class="font-medium">Suspended:</span> {{ $farmer->suspension_reason }}
                </div>
            @endif

            @if ($farmer->bio)
                <p class="mt-4 max-w-3xl text-soil-muted">{{ $farmer->bio }}</p>
            @endif
        </div>
    </section>

    <section class="mt-8 grid gap-4 sm:grid-cols-3">
        <x-ui.stat-card label="Total orders" :value="$orderStats['total_orders']" icon="tabler:clipboard-list" />
        <x-ui.stat-card label="Completed orders" :value="$orderStats['completed_orders']" icon="tabler:circle-check" tone="sky" />
        <x-ui.stat-card label="Revenue" icon="tabler:cash" tone="amber"
                        :value="\App\Helpers\MoneyFormatter::format($orderStats['revenue'])" />
    </section>

    <div class="mt-8 grid gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-ui.section-heading title="Products" />

            @if ($products->isEmpty())
                <div class="card">
                    <x-ui.empty-state icon="tabler:carrot" title="No products yet" />
                </div>
            @else
                <div class="card overflow-x-auto">
                    <table class="table-clean">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Availability</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td class="font-medium">{{ $product->name }}</td>
                                    <td class="text-soil-muted">{{ $product->category->name }}</td>
                                    <td><x-ui.money :amount="$product->price" /></td>
                                    <td class="text-soil-muted">{{ $product->stock_quantity }} {{ $product->unit }}</td>
                                    <td><x-ui.availability-badge :availability="$product->availability" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $products->links() }}</div>
            @endif
        </div>

        <div>
            <x-ui.section-heading title="Markets" />

            @if ($farmer->markets->isEmpty())
                <div class="card">
                    <x-ui.empty-state icon="tabler:map-pin-off" title="Not selling anywhere yet" />
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($farmer->markets as $market)
                        <div class="card flex items-center justify-between p-4">
                            <span class="font-medium">{{ $market->name }}</span>
                            <span class="text-sm text-soil-muted">Stall {{ $market->pivot->stall_number ?? '-' }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</x-layouts.panel>
