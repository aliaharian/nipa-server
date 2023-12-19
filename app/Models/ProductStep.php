<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStep extends Model
{
    use HasFactory;
    protected $fillable = [
        'global_step_id',
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

    public function getStepNameAttribute($value)
    {
        $keyword = Keyword::where('keyword', $value)->first();
        if ($keyword) {
            return $keyword->translation();
        }
        return $value;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function globalStep()
    {
        return $this->belongsTo(GlobalStep::class,'global_step_id');
    }

    //forms from product_step_forms table
    public function forms()
    {
        return $this->hasMany(ProductStepForm::class);
    }

    //roles
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'product_steps_roles');
    }

    //conditions
    public function conditions()
    {
        return $this->hasMany(ProductStepsCondition::class);
    }
}