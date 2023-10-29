<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProvinceCity extends Model
{
    use HasFactory;
    //table province_cities
    //id	name	province_id	created_at	updated_at
    protected $fillable = [
        'parent',
        'title',
        'sort'
    ];

    //provinces are all items that parent is 0
    public function scopeProvinces($query)
    {
        return $query->where('parent', 0);
    }
    public function scopeCity($query, $city)
    {
        return $query->where('parent', $city);
    }

}
