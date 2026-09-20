<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';
    protected $primaryKey = 'cate_id';

    protected $fillable = [
        'name',
        'cate_name',
        'user_id',
    ];

    protected $appends = ['name'];

    public function setNameAttribute($value)
    {
        $this->attributes['cate_name'] = $value;
    }

    public function getNameAttribute()
    {
        return $this->attributes['cate_name'] ?? null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'cate_id', 'cate_id');
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class, 'cate_id', 'cate_id');
    }
}
