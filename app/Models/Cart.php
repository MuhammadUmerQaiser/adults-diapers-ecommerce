<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class Cart extends Model
{
    use HasFactory;
    const CART_SESSION_PREFIX = 'cart_';
    const CART_SESSION_LIFETIME = 60 * 24 * 45; // 45 days in minutes

    private function getSessionKey(?string $cartToken): string
    {
        if (auth()->check()) {
            return self::CART_SESSION_PREFIX . 'user_' . auth()->id();
        }
        return self::CART_SESSION_PREFIX . $cartToken;
    }

    private function getCartFromSession(?string $cartToken): array
    {
        return Session::get($this->getSessionKey($cartToken), []);
    }

    private function saveCartToSession(?string $cartToken, array $cartItems): void
    {
        Session::put($this->getSessionKey($cartToken), $cartItems);
        Session::put(
            $this->getSessionKey($cartToken) . '_expires_at',
            now()->addMinutes(self::CART_SESSION_LIFETIME)->toDateTimeString()
        );
    }

    private function isCartExpired(?string $cartToken): bool
    {
        $expiresAt = Session::get($this->getSessionKey($cartToken) . '_expires_at');
        if (!$expiresAt)
            return false;
        return now()->isAfter($expiresAt);
    }

    private function buildCartItem(ProductVariation $variation, int $quantity): array
    {
        return [
            'variation_id' => $variation->id,
            'product_id' => $variation->product_id,
            'product_name' => $variation->product->name ?? null,
            'product_slug' => $variation->product->slug ?? null,
            'sku' => $variation->sku,
            'size' => $variation->size->name ?? null,
            'size_code' => $variation->size->code ?? null,
            'absorbency_level' => $variation->absorbency_level,
            'price' => (float) $variation->price,
            'quantity_per_pack' => $variation->quantity_per_pack,
            'quantity' => $quantity,
            'subtotal' => round((float) $variation->price * $quantity, 2),
            'stock' => $variation->stock,
            'image' => $variation->product->featuredImage->image_path ?? null,
            'added_at' => now()->toDateTimeString(),
        ];
    }

    public function addOrUpdateCart(Request $request)
    {
        try {
            $cartToken = $request->cart_token;
            $variationId = $request->variation_id;
            $quantity = (int) $request->quantity;

            if ($this->isCartExpired($cartToken)) {
                $this->saveCartToSession($cartToken, []);
            }

            $variation = ProductVariation::with(['product.featuredImage', 'size'])
                ->where('id', $variationId)
                ->where('is_active', 1)
                ->first();


            if ($variation->stock < 1) {
                return api_error('This product is out of stock.', 422);
            }

            $cartItems = $this->getCartFromSession($cartToken);

            if (isset($cartItems[$variationId])) {
                // ─── Existing item → adjust quantity ───
                $newQuantity = $quantity;
                // $newQuantity = $cartItems[$variationId]['quantity'] + $quantity;

                // Quantity 0 ya less → auto remove
                if ($newQuantity <= 0) {
                    unset($cartItems[$variationId]);
                    $this->saveCartToSession($cartToken, $cartItems);

                    return api_success([
                        'cart' => array_values($cartItems),
                        'summary' => $this->calculateSummary($cartItems),
                    ], 'Item removed from cart.');
                }

                // Stock check (only when adding)
                if ($quantity > 0 && $newQuantity > $variation->stock) {
                    return api_error("Only {$variation->stock} units available in stock.", 422);
                }

                $cartItems[$variationId]['quantity'] = $newQuantity;
                $cartItems[$variationId]['subtotal'] = round((float) $variation->price * $newQuantity, 2);
                $cartItems[$variationId]['stock'] = $variation->stock;
                $message = 'Cart updated successfully.';

            } else {
                // ─── New variation → add as new item ───
                if ($quantity < 0) {
                    return api_error('Item not found in cart.', 404);
                }

                if ($quantity > $variation->stock) {
                    return api_error("Only {$variation->stock} units available in stock.", 422);
                }

                $cartItems[$variationId] = $this->buildCartItem($variation, $quantity);
                $message = 'Item added to cart successfully.';
            }

            $this->saveCartToSession($cartToken, $cartItems);

            return api_success([
                'cart' => array_values($cartItems),
                'summary' => $this->calculateSummary($cartItems),
            ], $message);

        } catch (\Exception $e) {
            return api_error('Something went wrong while updating the cart.', 500, $e->getMessage());
        }
    }

    public function removeCartItem(Request $request)
    {
        try {
            $cartToken = $request->cart_token;
            $variationId = $request->variation_id;

            $cartItems = $this->getCartFromSession($cartToken);

            if (!isset($cartItems[$variationId])) {
                return api_error('Item not found in cart.', 404);
            }

            unset($cartItems[$variationId]);
            $this->saveCartToSession($cartToken, $cartItems);

            return api_success([
                'cart' => array_values($cartItems),
                'summary' => $this->calculateSummary($cartItems),
            ], 'Item removed from cart successfully.');

        } catch (\Exception $e) {
            return api_error('Something went wrong while removing the item.', 500, $e->getMessage());
        }
    }

    public function clearCart(Request $request)
    {
        try {
            $cartToken = $request->cart_token;

            Session::forget($this->getSessionKey($cartToken));
            Session::forget($this->getSessionKey($cartToken) . '_expires_at');

            return api_success(null, 'Cart cleared successfully.');

        } catch (\Exception $e) {
            return api_error('Something went wrong while clearing the cart.', 500, $e->getMessage());
        }
    }

    public function getCartItems(Request $request)
    {
        try {
            $cartToken = $request->cart_token;

            if ($this->isCartExpired($cartToken)) {
                $this->saveCartToSession($cartToken, []);
                return api_success([
                    'cart' => [],
                    'summary' => $this->calculateSummary([]),
                ], 'Cart is empty (session expired).');
            }

            $cartItems = $this->getCartFromSession($cartToken);
            $cartItems = $this->syncCartWithDatabase($cartItems);
            $this->saveCartToSession($cartToken, $cartItems);

            return api_success([
                'cart' => array_values($cartItems),
                'summary' => $this->calculateSummary($cartItems),
            ], 'Cart retrieved successfully.');

        } catch (\Exception $e) {
            return api_error('Something went wrong while retrieving the cart.', 500, $e->getMessage());
        }
    }

    public function getCartQuantity(Request $request)
    {
        try {
            $cartToken = $request->cart_token;

            if ($this->isCartExpired($cartToken)) {
                return api_success(['total_items' => 0, 'total_units' => 0], 'Cart is empty.');
            }

            $cartItems = $this->getCartFromSession($cartToken);

            return api_success([
                'total_items' => count($cartItems),
                'total_units' => array_sum(array_column($cartItems, 'quantity')),
            ], 'Cart quantity retrieved successfully.');

        } catch (\Exception $e) {
            return api_error('Something went wrong while retrieving cart quantity.', 500, $e->getMessage());
        }
    }

    /**
     * MERGE GUEST CART INTO USER CART (call after login)
     */
    public function mergeCarts(Request $request)
    {
        try {
            $guestSessionKey = self::CART_SESSION_PREFIX . $request->guest_cart_token;
            $userSessionKey = $this->getSessionKey($request->guest_cart_token);

            $guestCart = $this->getCartFromSession($guestSessionKey);
            $userCart = $this->getCartFromSession($userSessionKey);

            foreach ($guestCart as $variationId => $guestItem) {
                if (isset($userCart[$variationId])) {
                    $newQty = $userCart[$variationId]['quantity'] + $guestItem['quantity'];
                    $userCart[$variationId]['quantity'] = $newQty;
                    $userCart[$variationId]['subtotal'] = round($userCart[$variationId]['price'] * $newQty, 2);
                } else {
                    $userCart[$variationId] = $guestItem;
                }
            }

            $this->saveCartToSession($userSessionKey, $userCart);

            Session::forget($this->getSessionKey($guestSessionKey));
            Session::forget($this->getSessionKey($guestSessionKey) . '_expires_at');

            return api_success([
                'cart' => array_values($userCart),
                'summary' => $this->calculateSummary($userCart),
            ], 'Cart merged successfully.');

        } catch (\Exception $e) {
            return api_error('Something went wrong while merging carts.', 500, $e->getMessage());
        }
    }

    private function calculateSummary(array $cartItems): array
    {
        return [
            'total_items' => count($cartItems),
            'total_units' => array_sum(array_column($cartItems, 'quantity')),
            'subtotal' => round(array_sum(array_column($cartItems, 'subtotal')), 2),
        ];
    }

    private function syncCartWithDatabase(array $cartItems): array
    {
        if (empty($cartItems))
            return [];

        $variations = ProductVariation::with(['product.featuredImage', 'size'])
            ->whereIn('id', array_keys($cartItems))
            ->where('is_active', 1)
            ->get()
            ->keyBy('id');

        foreach ($cartItems as $variationId => $item) {
            if (!isset($variations[$variationId])) {
                unset($cartItems[$variationId]);
                continue;
            }

            $variation = $variations[$variationId];
            $qty = min($item['quantity'], $variation->stock);

            if ($qty < 1) {
                unset($cartItems[$variationId]);
                continue;
            }

            $cartItems[$variationId]['price'] = (float) $variation->price;
            $cartItems[$variationId]['stock'] = $variation->stock;
            $cartItems[$variationId]['quantity'] = $qty;
            $cartItems[$variationId]['subtotal'] = round((float) $variation->price * $qty, 2);
        }

        return $cartItems;
    }

}
