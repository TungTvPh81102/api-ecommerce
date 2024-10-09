<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'slug',
        'price',
        'discount_price',
        'stock',
        'image',
        'description',
        'content',
        'views',
        'status',
        'category_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class)->select('id', 'name');
    }

    public function variants()
    {
        return $this->hasMany(Variant::class);
    }
}
