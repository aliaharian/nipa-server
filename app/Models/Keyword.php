<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Keyword extends Model
{
    use HasFactory;
    protected $fillable = ['keyword'];

    function translation(){
        $lang = app()->getLocale();
        $language = Language::where('language',$lang)->first();
        if($language){
            $translation = Translation::where('language_id',$language->id)->where('keyword_id',$this->id)->first();
            if($translation){
                return $translation->translation;
            }
        }
        return $this->keyword;
    }

    function translations(){
        return $this->hasMany(Translation::class);
    }
}
