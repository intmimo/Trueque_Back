<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'image_path',
        'original_name',
        'order'
    ];

    // Relación: Una imagen pertenece a un producto
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Accessor para obtener la URL completa de la imagen
    public function getImageUrlAttribute()
    {
        return asset('storage/' . $this->image_path);
    }
}
