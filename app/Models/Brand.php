<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;

    protected $table = 'brands';
    protected $primaryKey = 'brand_id';

    protected $fillable = [
        'brand_name',
        'cate_id',
        'user_id',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'cate_id', 'cate_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id', 'brand_id');
    }
}
