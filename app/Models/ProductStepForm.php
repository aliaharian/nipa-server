<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStepForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_step_id',
        'form_id',
    ];

    public function productStep()
    {
        return $this->belongsTo(ProductStep::class , 'product_step_forms');
    }
}
