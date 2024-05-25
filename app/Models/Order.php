<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'user_id', 'customer_name', "count", "product_step_id","order_state_id"];

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
    public function step()
    {
        return $this->belongsTo(ProductStep::class, 'product_step_id', 'id');
    }
    public function rejectedInfo()
    {
        return $this->hasOne( RejectedOrderDetail::class, 'order_id', 'id');
    }

    //user answers
    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }
    public function state()
    {
        return $this->belongsTo(OrderState::class,'order_state_id','id');
    }
}
