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
}
