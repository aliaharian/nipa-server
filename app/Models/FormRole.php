<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class FormRole extends Pivot
{
    use HasFactory;

    protected $table = 'form_roles';
    protected $fillable = [
        'form_id',
        'role_id',
    ];

    // public function form()
    // {
    //     return $this->belongsTo(Form::class , 'form_roles');
    // }
}
