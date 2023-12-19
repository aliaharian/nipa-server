<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BasicData extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',

    ];

    public function items()
    {
        return $this->hasMany(BasicDataItem::class)->orderBy('id','desc');
    }
    
    public function getNameAttribute($value)
    {
        $keyword = Keyword::where('keyword', $value)->first();
        if ($keyword) {
            return $keyword->translation();
        }
        return $value;
    }
}