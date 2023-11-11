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
    //items
    public function factorItems()
    {
        return $this->hasMany(FactorItem::class);
    }
    //factor statuses
    public function factorStatuses()
    {
        return $this->hasMany(FactorStatus::class);
    }
    //factor payment steps
    public function factorPaymentSteps()
    {
        return $this->hasMany(FactorPaymentStep::class);
    }

    //last status
    public function lastStatus()
    {
        return $this->hasOne(FactorStatus::class)->latestOfMany();
    }
    
}
