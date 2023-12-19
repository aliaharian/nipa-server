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
        'count_type'
    ];

    //translate name based on app locale
    public function getNameAttribute($value)
    {
        $keyword = Keyword::where('keyword', $value)->first();
        if ($keyword) {
            return $keyword->translation();
        }
        return $value;
    }
    public function steps()
    {
        return $this->hasMany(ProductStep::class);
    }
    public function forms()
    {
        return $this->hasMany(Form::class);
    }
    public function initialOrderForm()
    {
        $initial = null;
        $forms = $this->forms;
        foreach ($forms as $form) {
            $step = null;
            if ($form->productSteps) {
                $step = $form->productSteps[0];
                if ($step->globalStep) {
                    if ($step->globalStep->description == "initialOrder") {
                        $initial = $form;
                    }
                }
            }
        }
        return $initial;
    }
    public function completeOrderForm()
    {
        $initial = null;
        $forms = $this->forms;
        foreach ($forms as $form) {
            $step = null;
            if ($form->productSteps) {
                $step = $form->productSteps[0];
                if ($step->globalStep) {
                    if ($step->globalStep->description == "completeOrder") {
                        $initial = $form;
                    }
                }
            }
        }
        return $initial;
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
