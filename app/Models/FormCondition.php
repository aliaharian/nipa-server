<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'form_field_id',
        'form_field_option_id',
        'operation',
        'relational_form_field_id',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function formField()
    {
        return $this->belongsTo(FormField::class);
    }

    public function formFieldOption()
    {
        return $this->belongsTo(FormFieldOption::class);
    }

    public function relationalFormField()
    {
        return $this->belongsTo(FormField::class, 'relational_form_field_id');
    }
}