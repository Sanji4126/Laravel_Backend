<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';
    protected $primaryKey = 'product_id';

    protected $fillable = [
        'pro_name',
        'product_name',
        'cate_id',
        'brand_id',
        'qty',
        'stock',
        'price',
        'description',
        'image',
        'user_id',
    ];

    protected $appends = ['pro_name', 'qty'];

    public function setProNameAttribute($value)
    {
        $this->attributes['product_name'] = $value;
    }

    public function getProNameAttribute()
    {
        return $this->attributes['product_name'] ?? null;
    }

    public function setQtyAttribute($value)
    {
        $this->attributes['stock'] = $value;
    }

    public function getQtyAttribute()
    {
        return $this->attributes['stock'] ?? null;
    }

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'cate_id', 'cate_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'brand_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'product_id', 'product_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id', 'product_id');
    }
}
