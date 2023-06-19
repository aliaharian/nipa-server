<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'file_id',
    ];

    //append hashcode always in result
    protected $appends = ['hashcode'];

    public function getHashcodeAttribute()
    {
        return $this->file->hash_code;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function file()
    {
        return $this->belongsTo(File::class);
    }

}
