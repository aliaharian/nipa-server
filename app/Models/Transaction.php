<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    //wallet_id - payment_method - price - status_id - description - meta - transaction_type - isValid
    protected $fillable = [
        'wallet_id',
        'payment_method',
        'price',
        'status_id',
        'description',
        'meta',
        'transaction_type',
        'isValid',
    ];

    //wallet
    public function wallet()
    {
        return $this->belongsTo(UserWallet::class, 'wallet_id', 'id');
    }
    //status
    public function status()
    {
        return $this->belongsTo(TransactionStatus::class, 'status_id', 'id');
    }
}
