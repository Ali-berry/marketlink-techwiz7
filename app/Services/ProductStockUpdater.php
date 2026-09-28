<?php

namespace App\Services;

use App\Enums\ProductAvailability;
use App\Models\Product;
use App\Notifications\ProductBackInStock;

// Product ka stock / availability yahan se badalta hai (product pages aur farmer AI dono).
// Product dobara order ke qabil bane to restock alert wale sab logon ko notification jati hai
class ProductStockUpdater
{
    public function update(Product $product, array $changes): void
    {
        $productWasOrderable = $product->canBeOrdered();

        $product->update($changes);

        if (! $productWasOrderable && $product->canBeOrdered()) {
            foreach ($product->customersWantingRestockAlert as $customer) {
                $customer->notify(new ProductBackInStock($product));
            }
        }
    }

    public function markSoldOut(Product $product): void
    {
        $this->update($product, ['availability' => ProductAvailability::SoldOut]);
    }

    // weekly stock refill - sold out product stock ho to wapas sale pe
    public function refillToWeeklyAmount(Product $product): void
    {
        $changes = ['stock_quantity' => $product->weekly_default_quantity];

        if ($product->availability === ProductAvailability::SoldOut && $product->weekly_default_quantity > 0) {
            $changes['availability'] = ProductAvailability::Available;
        }

        $this->update($product, $changes);
    }
}
