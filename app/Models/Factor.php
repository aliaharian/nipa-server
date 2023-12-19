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

    //calculate total price
    public function totalPrice($strict = false)
    {
        //map all factor items
        //if factor item count_typ is m2 => price = width*height*count*unit_price
        //if factor item count_typ is quantity => price = count*unit_price
        //final calculatable price of each item is => calculateablePrice = price - off_price + additional_price
        //sum all calculatablePrice of all items
        //return sum

        //return error if all of items doesnt have unit_price
        //check if all of items have unit_price
        $factorItems = $this->factorItems;
        $allItemsHaveUnitPrice = true;
        foreach ($factorItems as $factorItem) {
            if (!$factorItem->unit_price) {
                $allItemsHaveUnitPrice = false;
            }
        }
        if (!$allItemsHaveUnitPrice && $strict) {
            return response()->json([
                'message' => //persian
                'همه ی محصولات باید قیمت داشته باشند',
                'status' => 'error',
                'success' => false,
                'code' => 404
            ], 404);
        }

        $sumPrice = 0;
        $sumOffPrice = 0;
        $sumAdditionalPrice = 0;
        //calculate total price
        $sum = $this->factorItems->map(function ($item) use (&$sumPrice, &$sumOffPrice, &$sumAdditionalPrice) {
            $count = $item->count ?? 1;
            $unit_price = $item->unit_price ?? 0;
            $off_price = $item->off_price ?? 0;
            $additional_price = $item->additional_price ?? 0;
            $sumOffPrice += $off_price;
            $sumAdditionalPrice += $additional_price;
            if ($item->count_type == 'm2') {
                $sumPrice += ($item->width * $item->height * $count * $unit_price);
                return ($item->width * $item->height * $count * $unit_price) - $off_price + $additional_price;
            } else {
                $sumPrice += ($count * $unit_price);
                return ($count * $unit_price) - $off_price + $additional_price;
            }
        })->sum();
        return response()->json([
            'data' => $sum,
            'strictData' => $allItemsHaveUnitPrice ? $sum : 0,
            'printable' => number_format($sum),
            'status' => 'success',
            'allHavePrice' => $allItemsHaveUnitPrice,
            'sumPrice' => $sumPrice,
            'sumOffPrice' => $sumOffPrice,
            'sumAdditionalPrice' => $sumAdditionalPrice,
            'success' => true,
            'code' => 200
        ], 200);
    }
}
