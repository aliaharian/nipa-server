<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStep extends Model
{
    use HasFactory;
    protected $fillable = [
        'step_name',
        'product_id',
        'parent_step_id',
        'meta'
    ];
    //make timestamps hidden
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    //forms from product_step_forms table
    public function forms()
    {
        return $this->hasMany(ProductStepForm::class);
    }

}