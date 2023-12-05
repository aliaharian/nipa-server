<?php

namespace App\Http\Controllers\wallet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    //transactions list
    /**
     * @OA\Get(
     *   path="/v1/wallet/transactions",
     *   tags={"Wallet"},
     *   summary="show all transactions",
     *   description="show all transactions with related filters if user has access or only shows user transactions",
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   security={{ "apiAuth": {} }}
     *)
     **/
    public function transactionsList()
    {
        $transactions = auth()->user()->wallet->transactions;
        foreach ($transactions as $transaction) {
            $transaction->status;
        }
        //transaction_type is : incrreaseBalance - increaseCredit - Withdrawal 
        //translate to persian and pass to frontend
        $transactions = $transactions->map(function ($transaction) {
            if ($transaction->transaction_type == 'increaseBalance') {
                $transaction->transaction_type = 'افزایش موجودی';
            } elseif ($transaction->transaction_type == 'increaseCredit') {
                $transaction->transaction_type = 'افزایش اعتبار';
            } elseif ($transaction->transaction_type == 'Withdrawal') {
                $transaction->transaction_type = 'برداشت';
            }
            return $transaction;
        });

        //calculate remaining balance after every transaction
        $transactions = $transactions->map(function ($transaction) {
            $transaction->remainingBalance = $transaction->wallet->balance;
            return $transaction;
        });
        //order by created_at desc
        $transactions = $transactions->sortByDesc('created_at');
        return response()->json(['transactions' => $transactions], 200);
    }
}
