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
    protected $appends = ['file', 'payerDescription'];

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

    //file if fileId exists in meta json field
    public function getFileAttribute()
    {
        $meta = json_decode($this->meta);
        // return $meta;
        if (isset($meta->fileId)) {
            $file = File::find($meta->fileId);
            if ($file) {
                return $file->only(['id', 'hash_code']);
            } else {
                return null;
            }

        } else {
            return null;
        }
    }

    //payerDescription
    public function getPayerDescriptionAttribute()
    {
        $meta = json_decode($this->meta);
        if (isset($meta->payerDescription)) {
            return $meta->payerDescription;
        } else {
            return null;
        }
    }

    

}
