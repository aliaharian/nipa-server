<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'custom',
        'code',
        'status',
    ];
    public function steps()
    {
        return $this->hasMany(ProductStep::class);
    }
    public function forms()
    {
        return $this->hasMany(Form::class);
    }
    public function details()
    {
        return $this->hasMany(ProductDetail::class);
    }
    //images
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
    public function fieldValue($fieldName)
    {
        $forms = $this->forms;
        foreach ($forms as $form) {
            $fields = $form->fields;
            foreach ($fields as $field) {
                if ($field->name == $fieldName) {
                    return $field->defaultValue($form->id);
                }
            }
        }
    }
}