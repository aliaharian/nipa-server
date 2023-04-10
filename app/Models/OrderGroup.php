<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderGroup extends Model
{
    use HasFactory;
    protected $fillable = ['user_id' , 'total_price' , 'total_off'];

    public function orders(){
        return $this->belongsToMany(Order::class,'order_group_orders');
    }
    
}
