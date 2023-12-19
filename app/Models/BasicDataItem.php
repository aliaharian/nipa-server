<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BasicDataItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'basic_data_id',
        'code', 
        'status'
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
