<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    use HasFactory;
    protected $fillable = ['keyword_id', 'language_id', 'translation'];
    //append language attribute
    protected $appends = ['lang'];

    //add language field
    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    //add language attribute from this language id
    public function getLangAttribute()
    {
        return Language::find($this->language_id)->language;
    }
}
