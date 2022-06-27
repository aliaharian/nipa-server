<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormField extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'form_field_type_id',
        'label',
        'placeholder',
        'helper_text',
        'validation',
        'required',
        'min',
        'max',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    function type(){
        return $this->belongsTo(FormFieldType::class, 'form_field_type_id');
    }

    function options(){
        return $this->hasMany(FormFieldOptions::class);
    }
}
