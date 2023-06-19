<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStepsRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_step_id',
        'role_id'
    ];
}
