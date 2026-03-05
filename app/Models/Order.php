<?php

namespace App\Models;

use App\Http\Resources\OrderResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class Order extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    private array $statusFlow = [
        'pending' => 1,
        'processing' => 2,
        'shipped' => 3,
        'delivered' => 4,
        'cancelled' => 5,
    ];

    private function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $number = "{$prefix}-{$date}-{$random}";

        if (Order::where('order_number', $number)->exists()) {
            return $this->generateOrderNumber();
        }

        return $number;
    }

    private function getCartItems(int $userId): array
    {
        $sessionKey = 'cart_user_' . $userId;
        return Session::get($sessionKey, []);
    }

    private function clearUserCart(int $userId): void
    {
        $sessionKey = 'cart_user_' . $userId;
        Session::forget($sessionKey);
        Session::forget($sessionKey . '_expires_at');
    }

    public function createCheckoutSession(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $cartItems = $this->getCartItems($user->id);

            if (empty($cartItems)) {
                return api_error('Your cart is empty.', 422);
            }

            $shippingMethod = ShippingMethod::where('id', $request->shipping_method_id)
                ->where('is_active', 1)
                ->first();

            if (!$shippingMethod) {
                return api_error('Invalid or unavailable shipping method.', 422);
            }

            // Stock validation
            $variationIds = array_keys($cartItems);
            $variations = ProductVariation::whereIn('id', $variationIds)
                ->where('is_active', 1)
                ->get()
                ->keyBy('id');

            foreach ($cartItems as $variationId => $item) {
                if (!isset($variations[$variationId])) {
                    return api_error("Product '{$item['product_name']}' is no longer available.", 422);
                }
                if ($variations[$variationId]->stock < $item['quantity']) {
                    return api_error(
                        "Only {$variations[$variationId]->stock} units of '{$item['product_name']}' are available.",
                        422
                    );
                }
            }

            // Calculate totals
            $subtotal = round(array_sum(array_column($cartItems, 'subtotal')), 2);
            $shippingCost = (float) $shippingMethod->price;
            $total = round($subtotal + $shippingCost, 2);

            // ── Create order in DB right now (payment_status = pending) ──
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user->id,
                'shipping_method_id' => $shippingMethod->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'street_address' => $request->street_address,
                'city' => $request->city,
                'state' => $request->state,
                'zip_code' => $request->zip_code,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'status' => 'pending',
                'payment_status' => 'pending',
                'notes' => $request->notes ?? null,
            ]);

            // ── Save order details & deduct stock ──
            foreach ($cartItems as $variationId => $item) {
                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_variation_id' => $variationId,
                    'product_name' => $item['product_name'],
                    'sku' => $item['sku'],
                    'size' => $item['size'] ?? null,
                    'absorbency_level' => $item['absorbency_level'] ?? null,
                    'quantity_per_pack' => $item['quantity_per_pack'],
                    'unit_price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                ]);

                $variations[$variationId]->decrement('stock', $item['quantity']);
            }

            // ── Build Stripe line items ──
            $lineItems = [];
            foreach ($cartItems as $item) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'usd',
                        'unit_amount' => (int) round($item['price'] * 100),
                        'product_data' => [
                            'name' => $item['product_name'],
                            'description' => implode(' | ', array_filter([
                                $item['size'] ?? null,
                                $item['absorbency_level'] ?? null,
                                'Pack of ' . $item['quantity_per_pack'],
                            ])),
                        ],
                    ],
                    'quantity' => $item['quantity'],
                ];
            }

            if ($shippingMethod->price > 0) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'usd',
                        'unit_amount' => (int) round($shippingMethod->price * 100),
                        'product_data' => [
                            'name' => $shippingMethod->name . ' (Shipping)',
                        ],
                    ],
                    'quantity' => 1,
                ];
            }

            // ── Create Stripe session — pass order_id in metadata ──
            Stripe::setApiKey(config('services.stripe.secret'));

            $checkoutSession = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'customer_email' => $request->email,
                'success_url' => config('app.frontend_url') . '/order/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => config('app.frontend_url') . '/order/cancel?session_id={CHECKOUT_SESSION_ID}',
                'metadata' => [
                    'order_id' => (string) $order->id, // ← webhook uses this to find order
                ],
            ]);

            // Save stripe_session_id on order
            $order->update(['stripe_session_id' => $checkoutSession->id]);

            // Clear cart
            $this->clearUserCart($user->id);

            DB::commit();

            return api_success([
                'checkout_url' => $checkoutSession->url,
                'session_id' => $checkoutSession->id,
                'order_number' => $order->order_number,
            ], 'Checkout session created. Redirect user to checkout_url.');

        } catch (\Exception $e) {
            DB::rollBack();
            return api_error('Something went wrong while creating checkout session.', 500, $e->getMessage());
        }
    }

    // ─── STEP 2: Stripe Webhook ───────────────────────────────────────────────
    // Finds order via metadata.order_id — no session needed

    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret')
            );
        } catch (\Exception $e) {
            Log::error('Stripe webhook signature failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature.'], 400);
        }

        match ($event->type) {
            'checkout.session.completed' => $this->handlePaymentSuccess($event->data->object),
            'checkout.session.async_payment_failed',
            'checkout.session.expired' => $this->handlePaymentFailed($event->data->object),
            default => null,
        };

        return response()->json(['received' => true]);
    }

    private function handlePaymentSuccess(\Stripe\Checkout\Session $session): void
    {
        // Duplicate prevention
        if (Payment::where('stripe_session_id', $session->id)->exists()) {
            Log::info("Webhook duplicate: payment already processed for session {$session->id}");
            return;
        }

        DB::beginTransaction();
        try {
            // Find order via metadata.order_id (no session dependency)
            $order = Order::find($session->metadata->order_id ?? null);

            if (!$order) {
                Log::error("Webhook: order not found. metadata order_id: {$session->metadata->order_id}, session: {$session->id}");
                DB::rollBack();
                return;
            }

            $order->update(['payment_status' => 'paid']);

            Payment::create([
                'order_id' => $order->id,
                'stripe_session_id' => $session->id,
                'stripe_payment_intent' => $session->payment_intent,
                'amount' => $order->total,
                'currency' => $session->currency ?? 'usd',
                'status' => 'paid',
            ]);

            DB::commit();
            Log::info("Order {$order->order_number} payment confirmed.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('handlePaymentSuccess failed: ' . $e->getMessage());
        }
    }

    private function handlePaymentFailed(\Stripe\Checkout\Session $session): void
    {
        if (Payment::where('stripe_session_id', $session->id)->exists())
            return;

        DB::beginTransaction();
        try {
            $order = Order::with('orderDetails')->find($session->metadata->order_id ?? null);

            if (!$order)
                return;

            // Restore stock
            foreach ($order->orderDetails as $detail) {
                ProductVariation::where('id', $detail->product_variation_id)
                    ->increment('stock', $detail->quantity);
            }

            $order->update([
                'payment_status' => 'failed',
                'status' => 'cancelled',
            ]);

            Payment::create([
                'order_id' => $order->id,
                'stripe_session_id' => $session->id,
                'amount' => $order->total,
                'currency' => 'usd',
                'status' => 'failed',
            ]);

            DB::commit();
            Log::info("Order {$order->order_number} marked as payment_failed, stock restored.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('handlePaymentFailed failed: ' . $e->getMessage());
        }
    }

    public function getUserOrders(Request $request)
    {
        try {
            $user = auth()->user();
            $perPage = $request->per_page ?? 10;

            $orders = Order::with(['orderDetails', 'shippingMethod', 'payment'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $data = [
                'data' => OrderResource::collection($orders->items()),
                'meta' => $orders->toArray(),
            ];

            return api_success(paginate($data), 'Orders retrieved successfully.');

        } catch (\Exception $e) {
            return api_error('Something went wrong while retrieving orders.', 500, $e->getMessage());
        }
    }

    public function getOrderByNumber(string $orderNumber)
    {
        try {
            $user = auth()->user();

            $query = Order::with(['orderDetails', 'shippingMethod', 'user', 'payment'])
                ->where('order_number', $orderNumber);

            if ($user->role_id != 1) {
                $query->where('user_id', $user->id);
            }

            $order = $query->firstOrFail();

            return api_success(new OrderResource($order), 'Order retrieved successfully.');

        } catch (\Exception $e) {
            return api_error('Order not found.', 404, $e->getMessage());
        }
    }

    public function cancelOrder(string $orderNumber)
    {
        DB::beginTransaction();
        try {
            $user = auth()->user();

            $order = Order::with('orderDetails')
                ->where('order_number', $orderNumber)
                ->where('user_id', $user->id)
                ->firstOrFail();

            if (!in_array($order->status, ['pending', 'processing'])) {
                return api_error('This order cannot be cancelled at its current stage.', 422);
            }

            foreach ($order->orderDetails as $detail) {
                ProductVariation::where('id', $detail->product_variation_id)
                    ->increment('stock', $detail->quantity);
            }

            $order->update(['status' => 'cancelled']);

            DB::commit();
            return api_success(new OrderResource($order->fresh(['orderDetails', 'shippingMethod'])), 'Order cancelled successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return api_error('Something went wrong while cancelling the order.', 500, $e->getMessage());
        }
    }

    public function getAllOrders(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;

            $query = Order::with(['orderDetails', 'shippingMethod', 'user', 'payment'])
                ->orderBy('created_at', 'desc');

            if ($request->status) {
                $query->where('status', $request->status);
            }

            $orders = $query->paginate($perPage);

            $data = [
                'data' => OrderResource::collection($orders->items()),
                'meta' => $orders->toArray(),
            ];

            return api_success(paginate($data), 'All orders retrieved successfully.');

        } catch (\Exception $e) {
            return api_error('Something went wrong while retrieving orders.', 500, $e->getMessage());
        }
    }

    public function updateOrderStatus(Request $request, string $orderNumber)
    {
        try {
            $order = Order::where('order_number', $orderNumber)->firstOrFail();

            if ($order->status === 'cancelled') {
                return api_error('Cancelled order status cannot be changed.', 422);
            }

            $currentRank = $this->statusFlow[$order->status];
            $newRank = $this->statusFlow[$request->status];

            if ($newRank <= $currentRank) {
                return api_error("Cannot change status from '{$order->status}' to '{$request->status}'.", 422);
            }

            $order->update(['status' => $request->status]);

            $order->load(['orderDetails', 'shippingMethod', 'user']);
            return api_success(new OrderResource($order), 'Order status updated successfully.');

        } catch (\Exception $e) {
            return api_error('Order not found or could not be updated.', 404, $e->getMessage());
        }
    }

    public function getOrderBySessionId(string $sessionId)
    {
        try {
            $order = Order::with(['orderDetails', 'shippingMethod', 'payment'])
                ->where('stripe_session_id', $sessionId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            return api_success(new OrderResource($order), 'Order retrieved successfully.');

        } catch (\Exception $e) {
            return api_error('Order not found.', 404, $e->getMessage());
        }
    }
}
