<?php

namespace App\Exports;

use App\Models\Transaction;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExportTransactionsList implements FromCollection, WithHeadings
{

    protected $parameters;

    public function __construct($parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $transactionsList = $this->parameters;
        $transactions = $transactionsList->transactions;
        //sort keys of transactions manually
        //create custom collection
        $customCollection = collect($transactions)->map(function ($item) {
            // Add custom columns to each item in the collection
            return [
                'id' => $item->id,
                'dateTime'=> $item->updated_at,
                'customer_name' => $item->full_name,
                // 'customer_mobile' => $item->user_mobile,
                'payment_method' => $item->payment_method,
                'price' => number_format($item->price),
                'financial_impact'=> $item->financial_impact,
                'status' => $item->status->name,
                'description' => $item->description,
                'incomeSum'=>$item->remainingSum
                // Add more custom columns as needed
            ];
        });

        return $customCollection;
    }
    public function headings(): array
    {
        // Return an array of column headings
        return [
            //persian
            'شناسه',
            'تاریخ و زمان',
            'نام مشتری',
            // 'شماره موبایل مشتری',
            'روش پرداخت',
            'مبلغ',
            'تاثیر مالی',
            'وضعیت',
            'توضیحات',
            ' جمع درآمد پس از این تراکنش',

        ];
    }
}
