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
}
