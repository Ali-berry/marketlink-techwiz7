<?php

namespace App\Services;

use App\Exceptions\OrderModificationException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Customer cutoff se pehle quantity badal sakta hai. Products lock hote hain taake stock ka hisaab
// kisi aur checkout ke saath gadbad na ho.
class OrderItemQuantityUpdater
{
    /**
     * @param  array<int, int>  $newQuantitiesByOrderItemId
     *
     * @throws OrderModificationException
     */
    public function update(Order $order, array $newQuantitiesByOrderItemId): Order
    {
        if (! $order->customerCanStillChange()) {
            throw new OrderModificationException('This order is too close to pickup time to change.');
        }

        DB::transaction(function () use ($order, $newQuantitiesByOrderItemId) {
            $orderItems = $order->items()->whereIn('id', array_keys($newQuantitiesByOrderItemId))->get();

            $productIds = $orderItems->pluck('product_id')->filter()->sort()->values();
            $lockedProducts = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            foreach ($orderItems as $item) {
                $this->applyNewQuantity($item, $newQuantitiesByOrderItemId[$item->id], $lockedProducts);
            }

            $order->update(['total_amount' => $order->items()->sum('line_total')]);
        });

        return $order->fresh('items');
    }

    private function applyNewQuantity(OrderItem $item, int $newQuantity, Collection $lockedProducts): void
    {
        $quantityDelta = $newQuantity - $item->quantity;

        if ($quantityDelta === 0) {
            return;
        }

        $lockedProduct = $item->product_id ? $lockedProducts->get($item->product_id) : null;

        if ($quantityDelta > 0) {
            $stockAvailable = $lockedProduct->stock_quantity ?? 0;

            if ($stockAvailable < $quantityDelta) {
                throw new OrderModificationException(
                    "Only {$stockAvailable} more of {$item->product_name} available - lower the quantity and try again."
                );
            }

            $lockedProduct->decrement('stock_quantity', $quantityDelta);
        } else {
            $lockedProduct?->increment('stock_quantity', -$quantityDelta);
        }

        $item->update([
            'quantity' => $newQuantity,
            'line_total' => $item->unit_price * $newQuantity,
        ]);
    }
}
