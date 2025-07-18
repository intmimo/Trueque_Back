<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'location',
        'status',
        'wanted_item',
        'user_id'
    ];

    protected $casts = [
        'publication_date' => 'date',
    ];

    // Relación: Un producto pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Un producto puede tener muchas imágenes
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * Obtener solo la imagen principal
     */
    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    /**
     * Obtener todas las imágenes excepto la principal
     */
    public function secondaryImages()
    {
        return $this->hasMany(ProductImage::class)->where('is_primary', false)->orderBy('sort_order');
    }

    /**
     * Un producto puede tener muchos likes
     */
    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    /**
     * Usuarios que han dado like al producto (relación many-to-many)
     */
    public function likedBy()
    {
        return $this->belongsToMany(User::class, 'likes')->withTimestamps();
    }

    /**
     * Contar total de likes del producto
     */
    public function getTotalLikesAttribute()
    {
        return $this->likes()->count();
    }

    /**
     * Verificar si un usuario específico ha dado like
     */
    public function isLikedBy($userId)
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    /**
     * Obtener URL de la imagen principal
     */
    public function getPrimaryImageUrlAttribute()
    {
        $primaryImage = $this->primaryImage;
        return $primaryImage ? $primaryImage->image_url : null;
    }

    /**
     * Obtener todas las URLs de las imágenes
     */
    public function getImageUrlsAttribute()
    {
        return $this->images->map(function ($image) {
            return $image->image_url;
        });
    }
}
