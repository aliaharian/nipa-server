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

    //hidden timestams
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function getNameAttribute($value)
    {
        $keyword = Keyword::where('keyword', $value)->first();
        if ($keyword) {
            return $keyword->translation();
        }
        return $value;
    }
}


