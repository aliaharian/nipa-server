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
    
}
