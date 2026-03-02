<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function size()
    {
        return $this->belongsTo(Size::class);
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function featuredImage()
    {
        return $this->morphOne(Image::class, 'imageable')->where('is_featured', 1);
    }

    public function getPricePerPieceAttribute()
    {
        return round($this->price / $this->quantity_per_pack, 2);
    }

    public function getTotalPiecesAttribute()
    {
        return $this->stock * $this->quantity_per_pack;
    }
}
