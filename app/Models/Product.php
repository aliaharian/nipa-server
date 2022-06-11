<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'custom',
    ];
    public function steps()
    {
        return $this->hasMany(ProductStep::class);
    }
    public function forms()
    {
        return $this->hasMany(Form::class);
    }
    public function details()
    {
        return $this->hasMany(ProductDetail::class);
    }
}
