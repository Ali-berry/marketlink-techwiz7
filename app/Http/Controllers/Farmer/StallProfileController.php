<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\UpdateStallProfileRequest;
use App\Models\FarmerProfile;
use App\Models\Market;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StallProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $farmer = $request->user()->farmerProfile;

        abort_if(! $farmer, 403, 'Your farmer profile is missing. Please contact the admin.');

        return view('farmer.stall.edit', [
            'farmer' => $farmer->load('markets'),
            'markets' => Market::active()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateStallProfileRequest $request): RedirectResponse
    {
        $farmer = $request->user()->farmerProfile;
        $validated = $request->validated();

        if ($request->hasFile('cover_image')) {
            $this->deleteUploadedCoverImage($farmer);
            $validated['cover_image_path'] = $request->file('cover_image')->store('farmers', 'public');
        }

        $acceptsUrgentOrders = $request->boolean('accepts_urgent_orders');
        // urgent orders off to auto-confirm bhi off
        $aiAutoConfirmsUrgent = $acceptsUrgentOrders && $request->boolean('ai_auto_confirms_urgent');

        $farmer->update([
            'stall_name' => $validated['stall_name'],
            'contact_person' => $validated['contact_person'],
            'bio' => $validated['bio'],
            'farming_experience' => $validated['farming_experience'],
            'address' => $validated['address'],
            'order_cutoff_hours' => $validated['order_cutoff_hours'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'cover_image_path' => $validated['cover_image_path'] ?? $farmer->cover_image_path,
            'accepts_urgent_orders' => $acceptsUrgentOrders,
            'ai_auto_confirms_urgent' => $aiAutoConfirmsUrgent,
            // auto-confirm off to AI ready bhi off - AI sirf apna confirm kiya order ready karta hai
            'ai_marks_urgent_ready' => $aiAutoConfirmsUrgent && $request->boolean('ai_marks_urgent_ready'),
            'urgent_prep_minutes' => $validated['urgent_prep_minutes'] ?? $farmer->urgent_prep_minutes,
            'urgent_pickup_starts_at' => $validated['urgent_pickup_starts_at'] ?? $farmer->urgent_pickup_starts_at,
            'urgent_pickup_ends_at' => $validated['urgent_pickup_ends_at'] ?? $farmer->urgent_pickup_ends_at,
            'max_urgent_orders_per_hour' => $validated['max_urgent_orders_per_hour'],
        ]);

        $farmer->markets()->sync($this->buildMarketSyncData($validated));

        return redirect()->route('farmer.stall.edit')->with('success', 'Your stall profile was updated.');
    }

    // checked market ids + stall numbers ko sync() ki shape mein: [market_id => ['stall_number' => '...']]
    private function buildMarketSyncData(array $validated): array
    {
        $selectedMarketIds = $validated['markets'] ?? [];
        $stallNumbersByMarketId = $validated['stall_numbers'] ?? [];

        return collect($selectedMarketIds)->mapWithKeys(fn (int $marketId) => [
            $marketId => ['stall_number' => $stallNumbersByMarketId[$marketId] ?? null],
        ])->all();
    }

    private function deleteUploadedCoverImage(FarmerProfile $farmer): void
    {
        if ($farmer->cover_image_path && ! Str::startsWith($farmer->cover_image_path, 'images/')) {
            Storage::disk('public')->delete($farmer->cover_image_path);
        }
    }
}
