<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormFieldType extends Model
{
    use HasFactory;
    protected $fillable = [
        'type',
        'label',
        'has_options',
     ];
protected $hidden = [
        'created_at',
        'updated_at',
    ];

} 
