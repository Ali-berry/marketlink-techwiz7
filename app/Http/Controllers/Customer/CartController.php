<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\AddToBasketRequest;
use App\Http\Requests\Customer\UpdateBasketQuantityRequest;
use App\Models\Product;
use App\Services\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Cart $cart): View
    {
        return view('customer.cart.index', [
            'basketGroups' => $cart->groupedByFarmer(),
        ]);
    }

    public function store(AddToBasketRequest $request, Product $product, Cart $cart): RedirectResponse
    {
        abort_unless($product->canBeOrdered(), 422, 'This product cannot be ordered right now.');

        $cart->add($product, $request->validated()['quantity']);

        return back()->with('success', $product->name.' was added to your basket.');
    }

    public function update(UpdateBasketQuantityRequest $request, Product $product, Cart $cart): RedirectResponse
    {
        $cart->updateQuantity($product->id, $request->validated()['quantity']);

        return back()->with('success', 'Basket updated.');
    }

    public function destroy(Product $product, Cart $cart): RedirectResponse
    {
        $cart->remove($product->id);

        return back()->with('success', $product->name.' was removed from your basket.');
    }
}
