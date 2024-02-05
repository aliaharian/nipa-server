<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use App\Permissions\HasPermissionsTrait;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasPermissionsTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'last_name',
        'email',
        'mobile',
        'otp',
        'mobile_verified_at',
        'password',
        'avatar_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    //roles
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'users_roles');
    }

    //customer
    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    //wallet
    public function wallet()
    {
        return $this->hasOne(UserWallet::class);
    }

    //wallet
    public function avatar()
    {
        return $this->hasOne(File::class,"id","avatar_id")->select("hash_code");
    }



}
