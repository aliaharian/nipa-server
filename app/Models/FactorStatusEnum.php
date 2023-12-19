<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactorStatusEnum extends Model
{
    use HasFactory;

    public function getNameAttribute($value)
    {
        $keyword = Keyword::where('keyword', $value)->first();
        if ($keyword) {
            return $keyword->translation();
        }
        return $value;
    }
}
