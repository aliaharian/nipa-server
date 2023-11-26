<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactorPaymentStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'factor_id',
        'step_number',
        'payable_price',
        'allow_online',
        'allow_offline',
        'pay_time',
    ];

    public function factor()
    {
        return $this->belongsTo(Factor::class);
    }

    public function payments()
    {
        return $this->hasMany(FactorPayment::class, 'payment_step_id', 'id');
    }
    //status
    public function status()
    {
        if($this->payments()->count() == 0)
        {
            return PaymentStatus::where('slug', 'unpaid')->first();
        }
        //last payment status
        return $this->payments()->orderBy('id', 'desc')->first()->status;
    }
}
