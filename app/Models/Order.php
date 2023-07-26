<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'user_id', 'customer_name'];

    public function orderGroup()
    {
        //from order_group_orders table
        return $this->belongsToMany(OrderGroup::class, 'order_group_orders');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    //user answers
    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }
}