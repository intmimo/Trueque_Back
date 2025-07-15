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
        'publication_date',
        'status',
        'wanted_item',
    ];

    protected $casts = [
        'publication_date' => 'date',
    ];
}
