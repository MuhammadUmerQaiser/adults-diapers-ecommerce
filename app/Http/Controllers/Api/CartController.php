<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartRequest;
use App\Http\Requests\Cart\CartTokenRequest;
use App\Http\Requests\Cart\MergeCartRequest;
use App\Http\Requests\Cart\RemoveCartRequest;
use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected $cart;

    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }

    public function index(CartTokenRequest $request)
    {
        return $this->cart->getCartItems($request);
    }

    public function quantity(CartTokenRequest $request)
    {
        return $this->cart->getCartQuantity($request);
    }

    public function addOrUpdate(AddCartRequest $request)
    {
        return $this->cart->addOrUpdateCart($request);
    }

    public function remove(RemoveCartRequest $request)
    {
        return $this->cart->removeCartItem($request);
    }

    public function clear(CartTokenRequest $request)
    {
        return $this->cart->clearCart($request);
    }

    public function merge(MergeCartRequest $request)
    {
        return $this->cart->mergeCarts($request);
    }
}
