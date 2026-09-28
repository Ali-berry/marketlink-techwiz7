<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

// Basket sirf session mein hai [product_id => quantity]. Products har baar DB se fresh aate hain
// taake price aur stock hamesha current dikhen.
class Cart
{
    private const SESSION_KEY = 'basket';

    public function add(Product $product, int $quantity): void
    {
        $items = $this->rawItems();
        $items[$product->id] = ($items[$product->id] ?? 0) + $quantity;
        $this->save($items);
    }

    public function updateQuantity(int $productId, int $quantity): void
    {
        $items = $this->rawItems();

        if ($quantity <= 0) {
            unset($items[$productId]);
        } else {
            $items[$productId] = $quantity;
        }

        $this->save($items);
    }

    public function remove(int $productId): void
    {
        $items = $this->rawItems();
        unset($items[$productId]);
        $this->save($items);
    }

    public function quantityFor(int $productId): int
    {
        return $this->rawItems()[$productId] ?? 0;
    }

    public function isEmpty(): bool
    {
        return empty($this->rawItems());
    }

    public function totalItemCount(): int
    {
        return array_sum($this->rawItems());
    }

    // har farmer ki ek entry: farmer, items [product, quantity, lineTotal], subtotal.
    // Jo product hata diya gaya ya hide ho gaya wo chup chaap nikal jata hai
    public function groupedByFarmer(): Collection
    {
        $quantitiesByProductId = $this->rawItems();

        if (empty($quantitiesByProductId)) {
            return collect();
        }

        $lines = Product::visibleToCustomers()
            ->with('farmer')
            ->whereIn('id', array_keys($quantitiesByProductId))
            ->get()
            ->map(fn (Product $product) => [
                'product' => $product,
                'quantity' => $quantitiesByProductId[$product->id],
                'lineTotal' => $product->price * $quantitiesByProductId[$product->id],
            ]);

        return $lines
            ->groupBy(fn (array $line) => $line['product']->farmer_profile_id)
            ->map(fn (Collection $farmerLines) => [
                'farmer' => $farmerLines->first()['product']->farmer,
                'items' => $farmerLines->values(),
                'subtotal' => $farmerLines->sum('lineTotal'),
            ]);
    }

    public function clearFarmerGroup(int $farmerProfileId): void
    {
        $productIdsForFarmer = Product::where('farmer_profile_id', $farmerProfileId)->pluck('id');
        $items = $this->rawItems();

        foreach ($productIdsForFarmer as $productId) {
            unset($items[$productId]);
        }

        $this->save($items);
    }

    // basket ki saari ids, wo bhi jo farmer ne pause kiye ya admin ne hide kiye
    public function productIds(): array
    {
        return array_keys($this->rawItems());
    }

    private function rawItems(): array
    {
        return session(self::SESSION_KEY, []);
    }

    private function save(array $items): void
    {
        session([self::SESSION_KEY => $items]);
    }
}
