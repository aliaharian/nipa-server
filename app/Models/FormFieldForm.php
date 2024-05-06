<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormFieldForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_field_id',
        'form_id',
        'origin_form_id',
        'width'
    ];

    function form()
    {
        return $this->belongsTo(Form::class, 'form_id', 'id');
    }

    function originForm()
    {
        return $this->belongsTo(Form::class, 'origin_form_id', 'id');
    }


    function field()
    {
        return $this->belongsTo(FormField::class, 'form_field_id', 'id');
    }
}
