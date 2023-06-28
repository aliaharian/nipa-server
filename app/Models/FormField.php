<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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
        'order',
        "basic_data_id"
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    // protected $appends = [
    //     'label',
    //     'placeholder'
    // ];

    function type()
    {
        return $this->belongsTo(FormFieldType::class, 'form_field_type_id');
    }
    public function originForm()
    {
        return $this->belongsToMany(Form::class, 'form_field_forms', 'form_field_id', 'origin_form_id');

    }
    public function forms()
    {
        return $this->belongsToMany(Form::class, 'form_field_forms', 'form_field_id', 'form_id');

    }
    function options()
    {
        // if ($this->basic_data_id) {
        //     return $this->belongsTo(BasicData::class, 'basic_data_id');
        // } else {
        return $this->hasMany(FormFieldOptions::class);
        // }
    }
    function basicData()
    {
        return $this->belongsTo(BasicData::class, 'basic_data_id');

    }

    public function defaultValue($form_id)
    {
        $user = Auth::user();
        $product_id = Form::find($form_id)->product_id;
        $order = Order::where('user_id', $user->id)->where('product_id', $product_id)->first();
        if ($order) {
            $order_id = $order->id;

            $userAnswer = UserAnswer::where('form_field_id', $this->id)->where('user_id', $user->id)->where('order_id', $order_id)->first();
            if ($userAnswer) {
                if ($this->type->has_options == 1) {
                    $option = FormFieldOptions::where('option', $userAnswer->answer)->first();
                    return $option;
                } else {
                    return $userAnswer->answer;
                }
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    // public function setLabelAttribute(){
    //     $label = $this->label;
    //     $keyword = Keyword::where('keyword' , $this->label)->first();
    //     if($keyword){
    //         $label = $keyword->translation();
    //     }
    //     $this->attributes['label'] = $label;
    // }
    // public function getLabelAttribute(){
    //     $label = $this->label;
    //     $keyword = Keyword::where('keyword' , $this->label)->first();
    //     if($keyword){
    //         $label = $keyword->translation();
    //     }
    //     return $this->attributes['label'] = $label;

    // }

    // public function getPlaceholderAttribute(){
    //     $placeholder = $this->placeholder;
    //     $keyword = Keyword::where('keyword' , $this->placeholder)->first();
    //     if($keyword){
    //         $placeholder = $keyword->translation();
    //     }
    //     $this->attributes['placeholder'] = $placeholder;
    // }
}