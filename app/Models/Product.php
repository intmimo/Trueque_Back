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
}
