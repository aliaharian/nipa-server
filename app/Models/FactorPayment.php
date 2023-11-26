<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactorPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_step_id',
        'transaction_id',
        'description',
        'meta',
        'payment_status_id',
        'wallet_payment_amount'
    ];

    //status
    public function status()
    {
        return $this->belongsTo(PaymentStatus::class, 'payment_status_id', 'id');
    }

    //transaction
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }
}
