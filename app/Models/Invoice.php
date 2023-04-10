<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_group_id',
        'off_percent'
    ];

    public function items(){
        return $this->hasMany(InvoiceItem::class);
    }
}
