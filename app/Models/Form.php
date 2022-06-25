<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'product_id',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class , 'form_roles' , 'form_id' , 'role_id');
    }

    public function productSteps()
    {
        return $this->belongsToMany(ProductStep::class , 'product_step_forms' , 'form_id' , 'product_step_id');
    }
}
