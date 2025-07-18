<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Like extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relaciones

    // Un like pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Un like pertenece a un producto
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes útiles

    /**
     * Scope para obtener likes de un usuario específico
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para obtener likes de un producto específico
     */
    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Verificar si un usuario ya dio like a un producto
     */
    public static function hasLiked($userId, $productId)
    {
        return self::where('user_id', $userId)
                ->where('product_id', $productId)
                ->exists();
    }
}
