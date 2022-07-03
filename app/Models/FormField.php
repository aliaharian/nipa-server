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
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    // protected $appends = [
    //     'label',
    //     'placeholder'
    // ];

    function type(){
        return $this->belongsTo(FormFieldType::class, 'form_field_type_id');
    }

    function options(){
        return $this->hasMany(FormFieldOptions::class);
    }
    public function defaultValue($form_id)
    {
        $user = Auth::user();
        $product_id = Form::find($form_id)->product_id;
        $order_id = Order::where('user_id',$user->id)->where('product_id',$product_id)->first()->id;
        $userAnswer = UserAnswer::where('form_field_id', $this->id)->where('user_id',$user->id)->where('order_id',$order_id)->first();
        if($userAnswer){
            if($this->type->has_options == 1){
                $option = FormFieldOptions::where('option',$userAnswer->answer)->first();
                return $option;
            }else{
            return $userAnswer->answer;
            }
        }else{
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
