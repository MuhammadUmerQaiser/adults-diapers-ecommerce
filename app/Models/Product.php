<?php

namespace App\Models;

use App\Http\Resources\ProductResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function featuredImage()
    {
        return $this->morphOne(Image::class, 'imageable')
            ->where('is_featured', 1);
    }

    private function generateProductSKU($productName)
    {
        $prefix = 'AD'; // Adult Diapers
        $nameCode = strtoupper(substr(str_replace(' ', '', $productName), 0, 4));
        $random = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

        $sku = "{$prefix}-{$nameCode}-{$random}";

        // Check if SKU exists
        if (Product::where('sku', $sku)->exists()) {
            return $this->generateProductSKU($productName);
        }

        return $sku;
    }

    private function generateVariationSKU($productSKU, $sizeId, $absorbencyLevel = null)
    {
        $size = Size::find($sizeId);
        $sizeCode = $size ? $size->code : 'U';

        $absorbencyCode = '';
        if ($absorbencyLevel) {
            $absorbencyMap = [
                'Light' => 'LT',
                'Moderate' => 'MD',
                'Heavy' => 'HV',
                'Overnight' => 'ON'
            ];
            $absorbencyCode = '-' . $absorbencyMap[$absorbencyLevel];
        }

        $sku = "{$productSKU}-{$sizeCode}{$absorbencyCode}";
        if (ProductVariation::where('sku', $sku)->exists()) {
            $sku .= '-' . rand(10, 99);
        }

        return $sku;
    }

    public function getAllProducts(Request $request)
    {
        try {
            $query = Product::with(['category', 'variations.size', 'images'])
                ->where('is_active', 1)
                ->orderBy('created_at', 'desc');

            if ($request->paginated) {
                $perPage = $request->pagination ?? 10;
                $products = $query->paginate($perPage);

                $data = [
                    'data' => ProductResource::collection($products->items()),
                    'meta' => $products->toArray()
                ];

                return api_success(paginate($data), 'Products retrieved successfully');
            } else {
                $products = $query->get();
                return api_success(ProductResource::collection($products), 'Products retrieved successfully');
            }
        } catch (\Exception $e) {
            return api_error('Something went wrong while retrieving the products.', 500, $e->getMessage());
        }
    }

    public function addProduct(Request $request)
    {
        DB::beginTransaction();
        try {
            $productSKU = $this->generateProductSKU($request->name);
            $product = Product::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name) . '-' . time(),
                'description' => $request->description,
                'sku' => $productSKU,
                'category_id' => $request->category_id ?? 1,
                'is_active' => $request->is_active ?? 1,
            ]);

            if ($request->hasFile('images')) {
                $featuredIndex = $request->featured_image_index ?? 0;

                foreach ($request->file('images') as $index => $image) {
                    $savedFile = saveFile($image, 'images/products', $image->getClientOriginalName());

                    $product->images()->create([
                        'image_path' => $savedFile['path'],
                        'is_featured' => $index === (int) $featuredIndex ? 1 : 0,
                        'alt_text' => $request->name,
                    ]);
                }
            }

            foreach ($request->variations as $variationData) {
                $variationSKU = $this->generateVariationSKU(
                    $productSKU,
                    $variationData['size_id'],
                    $variationData['absorbency_level'] ?? null
                );
                ProductVariation::create([
                    'product_id' => $product->id,
                    'size_id' => $variationData['size_id'],
                    'sku' => $variationSKU,
                    'price' => $variationData['price'],
                    'quantity_per_pack' => $variationData['quantity_per_pack'],
                    'stock' => $variationData['stock'],
                    'absorbency_level' => $variationData['absorbency_level'] ?? null,
                    'is_active' => $variationData['is_active'] ?? 1,
                ]);
            }

            DB::commit();
            $product->load(['variations.size', 'images', 'category']);
            return api_success(new ProductResource($product), 'Product created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return api_error('Something went wrong while creating the product.', 500, $e->getMessage());
        }
    }

    public function getProductBySlug($slug)
    {
        try {
            $product = Product::with(['category', 'variations.size', 'images'])
                ->where('slug', $slug)
                ->where('is_active', 1)
                ->firstOrFail();

            return api_success(new ProductResource($product), 'Product retrieved successfully');

        } catch (\Exception $e) {
            return api_error('Product not found', 404, $e->getMessage());
        }
    }

    public function deleteProduct($id)
    {
        try {
            $product = Product::with('images')->findOrFail($id);

            foreach ($product->images as $image) {
                if ($image->image_path && file_exists(public_path($image->image_path))) {
                    unlink(public_path($image->image_path));
                }
            }

            $product->delete();

            return api_success(null, 'Product deleted successfully');

        } catch (\Exception $e) {
            return api_error('Product not found or could not be deleted', 404, $e->getMessage());
        }
    }

    public function updateProduct(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $product = Product::with(['images', 'variations'])->findOrFail($id);
           
            $product->update([
                'name' => $request->name ?? $product->name,
                'slug' => $request->name ? Str::slug($request->name) . '-' . time() : $product->slug,
                'description' => $request->description ?? $product->description,
                'category_id' => $request->category_id ?? $product->category_id,
                'is_active' => $request->is_active ?? $product->is_active,
            ]);

            // Handle images
            if ($request->has('delete_image_ids') && is_array($request->delete_image_ids)) {
                $imagesToDelete = $product->images()->whereIn('id', $request->delete_image_ids)->get();

                foreach ($imagesToDelete as $image) {
                    if ($image->image_path && file_exists(public_path($image->image_path))) {
                        unlink(public_path($image->image_path));
                    }
                    $image->delete();
                }
            }

            // Handle new image UPLOADS
            if ($request->hasFile('images')) {
                $featuredIndex = $request->featured_image_index;

                // Agar featured image change karni hai tou pehle sab se featured hata do
                if ($featuredIndex !== null) {
                    $product->images()->update(['is_featured' => 0]);
                }

                foreach ($request->file('images') as $index => $image) {
                    $savedFile = saveFile($image, 'images/products', $image->getClientOriginalName());

                    $product->images()->create([
                        'image_path' => $savedFile['path'],
                        'is_featured' => $index === (int) $featuredIndex ? 1 : 0,
                        'alt_text' => $request->name ?? $product->name,
                    ]);
                }
            }

            // Agar koi featured image nahi hai tou pehli image ko featured bana do
            if ($product->fresh()->images()->where('is_featured', 1)->count() === 0) {
                $product->images()->first()?->update(['is_featured' => 1]);
            }

            // Handle variations
            if ($request->has('variations')) {
                foreach ($request->variations as $variationData) {

                    // Update existing variation
                    if (!empty($variationData['id'])) {
                        $variation = ProductVariation::find($variationData['id']);
                        if ($variation && $variation->product_id === $product->id) {
                            $variation->update([
                                'size_id' => $variationData['size_id'] ?? $variation->size_id,
                                'price' => $variationData['price'] ?? $variation->price,
                                'quantity_per_pack' => $variationData['quantity_per_pack'] ?? $variation->quantity_per_pack,
                                'stock' => $variationData['stock'] ?? $variation->stock,
                                'absorbency_level' => $variationData['absorbency_level'] ?? $variation->absorbency_level,
                                'is_active' => $variationData['is_active'] ?? $variation->is_active,
                            ]);
                        }
                    } else {
                        // Create new variation
                        $variationSKU = $this->generateVariationSKU(
                            $product->sku,
                            $variationData['size_id'],
                            $variationData['absorbency_level'] ?? null
                        );
                        ProductVariation::create([
                            'product_id' => $product->id,
                            'size_id' => $variationData['size_id'],
                            'sku' => $variationSKU,
                            'price' => $variationData['price'],
                            'quantity_per_pack' => $variationData['quantity_per_pack'],
                            'stock' => $variationData['stock'],
                            'absorbency_level' => $variationData['absorbency_level'] ?? null,
                            'is_active' => $variationData['is_active'] ?? 1,
                        ]);
                    }
                }
            }

            DB::commit();
            $product->load(['variations.size', 'images', 'category']);
            return api_success(new ProductResource($product), 'Product updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return api_error('Something went wrong while updating the product.', 500, $e->getMessage());
        }
    }



}
