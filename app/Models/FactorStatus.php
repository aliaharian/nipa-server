<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactorStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'factor_id',
        'factor_status_enum_id',
        'name',
        'description',
    ];

    //hidden timestamps
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function factor()
    {
        return $this->belongsTo(Factor::class);
    }

    public function factorStatusEnum()
    {
        return $this->belongsTo(FactorStatusEnum::class);
    }
}
