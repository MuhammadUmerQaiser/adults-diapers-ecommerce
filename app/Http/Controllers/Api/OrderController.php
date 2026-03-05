<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\PlaceOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    // public function store(PlaceOrderRequest $request)
    // {
    //     return $this->order->placeOrder($request);
    // }

    public function checkout(PlaceOrderRequest $request)
    {
        return $this->order->createCheckoutSession($request);
    }

    // POST /webhook/stripe — Stripe calls this after payment (no auth, no CSRF)
    public function webhook(Request $request)
    {
        return $this->order->handleWebhook($request);
    }

    public function index()
    {
        return $this->order->getUserOrders(request());
    }

    public function show(string $orderNumber)
    {
        return $this->order->getOrderByNumber($orderNumber);
    }

    public function cancel(string $orderNumber)
    {
        return $this->order->cancelOrder($orderNumber);
    }

    public function adminIndex()
    {
        return $this->order->getAllOrders(request());
    }

    public function updateStatus(UpdateOrderStatusRequest $request, string $orderNumber)
    {
        return $this->order->updateOrderStatus($request, $orderNumber);
    }

    public function showBySession(string $sessionId)
    {
        return $this->order->getOrderBySessionId($sessionId);
    }
}
