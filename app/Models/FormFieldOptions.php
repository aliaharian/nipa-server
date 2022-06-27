<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormFieldOptions extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_field_id',
        'option',
        'label',
    ];
}
