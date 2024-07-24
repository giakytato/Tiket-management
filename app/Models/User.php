<?php

namespace App\Models;

use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'avatar',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Str::upper($value)
        );
    }

    protected function isAdmin(): Attribute
    {
        $admins = ['giacomoucci@gmail.com'];
        return Attribute::make(
            get: fn () => in_array($this->email, $admins)
        );
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
