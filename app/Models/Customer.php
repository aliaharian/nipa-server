<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    //user_id	phone	city_id	address	postal_code	national_code	code	

    protected $fillable = [
        'user_id',
        'phone',
        'city_id',
        'address',
        'postal_code',
        'national_code',
        'code',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function city()
    {
        return $this->belongsTo(ProvinceCity::class);
    }

    //province is the item in province_cities table that the id if it is the parent of city_id
    public function province()
    {
        return $this->belongsTo(ProvinceCity::class, 'city_id', 'id')->where('parent', 0);
    }
    
}
