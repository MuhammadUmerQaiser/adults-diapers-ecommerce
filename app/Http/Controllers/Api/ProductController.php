<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\AddProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $product;
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function index(Request $request)
    {
        return $this->product->getAllProducts($request);
    }

    public function store(AddProductRequest $request)
    {
        return $this->product->addProduct($request);
    }

    public function show($slug)
    {
        return $this->product->getProductBySlug($slug);
    }

    public function destroy($id)
    {
        return $this->product->deleteProduct($id);
    }

    public function update(UpdateProductRequest $request, $id)
    {
        return $this->product->updateProduct($request, $id);
    }
}
