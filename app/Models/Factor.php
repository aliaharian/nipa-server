<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Factor extends Model
{
    use HasFactory;
    // code	order_group_id	expire_date	description	created_at	updated_at	
    protected $fillable = [
        'code',
        'order_group_id',
        'expire_date',
        'description',
    ];

    public function orderGroup()
    {
        return $this->belongsTo(OrderGroup::class);
    }
}
