<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'colonia',
        'municipio',
        'rating',
        'profile_photo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'rating' => 'float',
    ];

    // Relaciones

    /**
     * Un usuario puede tener muchos productos
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Un usuario puede dar muchos "me gusta"
     */
    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    /**
     * Productos que el usuario ha likeado (relación many-to-many)
     */
    public function likedProducts()
    {
        return $this->belongsToMany(Product::class, 'likes')->withTimestamps();
    }

    /**
     * Verificar si el usuario ha dado like a un producto
     */
    public function hasLiked($productId)
    {
        return $this->likes()->where('product_id', $productId)->exists();
    }

    // Métodos adicionales

    /**
     * Obtener el tiempo en la app (días desde registro)
     */
    public function getDaysInAppAttribute()
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Obtener ubicación completa
     */
    public function getFullLocationAttribute()
    {
        return $this->colonia . ', ' . $this->municipio;
    }

    /**
     * Obtener URL completa de la foto de perfil
     */
    public function getProfilePhotoUrlAttribute()
    {
        if ($this->profile_photo) {
            return Storage::url($this->profile_photo);
        }
        
        // Retorna una imagen por defecto o null
        return asset('images/default-avatar.png'); // o return null;
    }

    /**
     * Eliminar foto de perfil anterior
     */
    public function deleteOldProfilePhoto()
    {
        if ($this->profile_photo && Storage::exists($this->profile_photo)) {
            Storage::delete($this->profile_photo);
        }
    }
}
