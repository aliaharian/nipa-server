<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStepsCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_step_id',
        'form_field_id',
        'form_field_option_id',
        'next_product_step_id',
        'basic_data_item_id'
    ];
}
