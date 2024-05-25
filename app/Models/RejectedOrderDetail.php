<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RejectedOrderDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        "order_id",
        "sales_description",
        "financial_description",
        "has_refund",
        "transaction_id"
    ];
}
