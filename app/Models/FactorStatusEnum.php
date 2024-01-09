<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactorStatusEnum extends Model
{
    use HasFactory;

    protected $appends = ['json'];

    public function getNameAttribute($value)
    {
        $keyword = Keyword::where('keyword', $value)->first();
        if ($keyword) {
            return $keyword->translation();
        }
        return $value;
    }

    public function getJsonAttribute()
    {
        if ($this->meta) {
            return json_decode($this->meta);
        }else{
            return "ali";
        }
    }
}
