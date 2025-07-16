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
}
