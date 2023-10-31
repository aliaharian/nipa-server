<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactorItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'factor_id',
        'order_id',
        'product_id',
        'code',
        'name',
        'count_type',
        'width',
        'height',
        'count',
        'unit_price',
        'off_price',
        'additional_price',
        'description',
    ];
}
