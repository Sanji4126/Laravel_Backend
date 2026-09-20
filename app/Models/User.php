<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = 'users';
    public function getJWTIdentifier()
        {
            return $this->getKey();
        }
  
        public function getJWTCustomClaims(): array
    {
        return [
            'name' => $this->user_name,
            'email' => $this->email,
            'role' => $this->role,
        ];
    }

    

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'user_name',
        'email',
        'password',
        'role',
    ];

    protected $appends = ['name'];

    public function setNameAttribute($value)
    {
        $this->attributes['user_name'] = $value;
    }

    public function getNameAttribute()
    {
        return $this->attributes['user_name'] ?? null;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'user_id', 'user_id');
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class, 'user_id', 'user_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'user_id', 'user_id');
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class, 'user_id', 'user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id', 'user_id');
    }

    public function refreshTokens(): HasMany
    {
        return $this->hasMany(RefreshToken::class, 'user_id', 'user_id');
    }
}
