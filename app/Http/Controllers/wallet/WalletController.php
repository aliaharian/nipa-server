<?php

namespace App\Http\Controllers\wallet;

use App\Exports\ExportTransactionsList;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\User;
use App\Models\UserWallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class WalletController extends Controller
{
    //transactions list
    /**
     * @OA\Get(
     *   path="/v1/wallet/transactions",
     *   tags={"Wallet"},
     *   summary="show all transactions",
     *   description="show all transactions with related filters if user has access or only shows user transactions",
     *   @OA\Parameter(
     *     name="user_id",
     *    description="user id",
     *    in="query",
     *   required=false,
     *   @OA\Schema(
     *  type="integer"
     *  )
     * ),
     *  @OA\Parameter(
     *     name="transaction_status_id",
     *    description="transaction status id",
     *    in="query",
     *   required=false,
     *   @OA\Schema(
     *  type="integer"
     *  )
     * ),
     *  @OA\Parameter(
     *    name="is_valid",
     *   description="is valid",
     *  in="query",
     * required=false,
     * @OA\Schema(
     * type="boolean"
     * )
     * ),
     * @OA\Parameter(
     *   name="date_from",
     * description="date from",
     * in="query",
     * required=false,
     * @OA\Schema(
     * type="string",
     * format="date"
     * )
     * ),
     * @OA\Parameter(
     *  name="date_to",
     * description="date to",
     * in="query",
     * required=false,
     * @OA\Schema(
     * type="string",
     * format="date"
     * )
     * ),
     * @OA\Parameter(
     * name="transaction_type",
     * description="transaction type",
     * in="query",
     * required=false,
     * @OA\Schema(
     * type="string",
     * enum={"increaseBalance", "increaseCredit", "Withdrawal","refund","allDeposits"}
     * )
     * ),
     * @OA\Parameter(
     * name="payment_method",
     * description="payment method",
     * in="query",
     * required=false,
     * @OA\Schema(
     * type="string",
     * enum={"online", "offline"}
     * )
     * ),
     * @OA\Parameter(
     * name="page",
     * description="page",
     * in="query",
     * required=false,
     * @OA\Schema(
     * type="integer"
     * )
     * ),
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
    public function transactionsList(Request $request, $export = false)
    {

        $permissions = Auth::user()->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug')->toArray();
        })->toArray();

        if (in_array('can-view-all-transactions', $permissions)) {
            $filters = request()->all();
            $query = Transaction::query();

            if (isset($filters['user_id'])) {
                $query->where('wallet_id', $filters['user_id']);
            }

            if (isset($filters['is_valid'])) {
                $query->where('isValid', $filters['is_valid']);
            }

            if (isset($filters['date_from'])) {
                $query->whereDate('updated_at', '>=', Carbon::parse($filters['date_from'])->format('Y-m-d'));
            }

            if (isset($filters['date_to'])) {
                $query->whereDate('updated_at', '<=', Carbon::parse($filters['date_to'])->format('Y-m-d'));
            }

            if (isset($filters['transaction_type'])) {
                if ($filters['transaction_type'] == 'allDeposits') {
                    $query->whereIn('transaction_type', ['increaseBalance', 'increaseCredit', 'refund']);
                } else {
                    $query->where('transaction_type', $filters['transaction_type']);
                }
            }
            if (isset($filters['transaction_status_id'])) {
                $query->where('status_id', $filters['transaction_status_id']);
            }

            if (isset($filters['payment_method'])) {
                $query->where('payment_method', $filters['payment_method']);
            }

            // Retrieve transactions
            $transactions = $export ?
                $query->orderBy('updated_at', 'DESC')->get()
                : $query->orderBy('updated_at', 'DESC')->paginate(10);
            $remainArray = $this->adminFinancialReport(
                [
                    'user_id' => $filters['user_id'] ?? null,
                    'is_valid' => $filters['is_valid'] ?? null,
                    'date_from' => $filters['date_from'] ?? null,
                    'date_to' => $filters['date_to'] ?? null,
                    'transaction_type' => $filters['transaction_type'] ?? null,
                    'payment_method' => $filters['payment_method'] ?? null,
                ]
            );
        } else {
            $wallet = auth()->user()->wallet;
            $transactions = Transaction::where('wallet_id', $wallet->id)->orderBy('updated_at', 'DESC')->paginate(10);
            $remainArray = $this->calculateRemainingAmountForUser(auth()->user()->id);
        }
        // $wallet = auth()->user()->wallet;
        // $transactions = Transaction::where('wallet_id', $wallet->id)->orderBy('updated_at', 'DESC')->paginate(4);
        foreach ($transactions as $transaction) {
            $transaction->status;
        }
        $pagination = $export ? [] : [
            'total' => $transactions->total(),
            'count' => $transactions->count(),
            'per_page' => $transactions->perPage(),
            'current_page' => $transactions->currentPage(),
            'total_pages' => $transactions->lastPage(),
        ];
        //transaction_type is : incrreaseBalance - increaseCredit - Withdrawal
        //translate to persian and pass to frontend
        $transactions = $transactions->map(function ($transaction) {
            if ($transaction->transaction_type == 'increaseBalance') {
                $transaction->transaction_type = 'افزایش موجودی';
            } elseif ($transaction->transaction_type == 'increaseCredit') {
                $transaction->transaction_type = 'افزایش اعتبار';
            } elseif ($transaction->transaction_type == 'Withdrawal') {
                $transaction->transaction_type = 'برداشت';
            } else if ($transaction->transaction_type == 'refund') {
                $transaction->transaction_type = 'بازگشت وجه';
            }
            return $transaction;
        });

        //calculate remaining balance after every transaction
        $transactions = $transactions->map(function ($transaction) {
            $transaction->remainingBalance = $transaction->wallet->balance;
            return $transaction;
        });
        //order by created_at desc
        // $transactions = $transactions->sortByDesc('updated_at');
        // $remainArray = $this->adminFinancialReport();
        //append remaining balance and credit to each transaction
        $transactions = $transactions->map(function ($transaction) use ($remainArray) {
            foreach ($remainArray['results'] as $remain) {
                if ($remain['transaction_id'] == $transaction->id) {
                    // $transaction->remainingBalance = $remain['remaining_balance'];
                    // $transaction->remainingCredit = $remain['remaining_credit'];
                    $transaction->remainingSum = $remain['remaining_sum'] ?? $remain['admin_financial_tolerance'];
                    $transaction->financial_impact = $remain['financial_impact'] ?? null;
                    $transaction->increase = $remain['transaction_type'] == "Withdrawal" ? false : true;
                    $transaction->full_name = $transaction->wallet->user->name ? $transaction->wallet->user->name . ' ' . $transaction->wallet->user->last_name : $transaction->wallet->user->mobile;
                }
            }
            return $transaction;
        });
        //dont return wallet object in each transaction
        $transactions = $transactions->map(function ($transaction) {
            unset($transaction->wallet);
            return $transaction;
        });
        $accessAll = in_array('can-view-all-transactions', $permissions);
        //add pagination data to transaction
        //add filters to resp
        $filters = request()->all(); //except page
        unset($filters['page']);
        return response()->json([
            'transactions' => $transactions,
            'sum' => $accessAll ? null : $remainArray['sum'],
            'sum2' => $accessAll ? $remainArray['sum'] : null,
            'accessAll' => $accessAll,
            'pagination' => $pagination,
            'filters' => $filters,
        ], 200);
        // return response()->json(['transactions' => $remainArray], 200);
    }


    public function calculateRemainingAmountForUser($userId)
    {
        $user = User::find($userId);
        // Get the user's wallet
        $wallet = $user->wallet;

        if (!$wallet) {
            // Handle the case where the user's wallet is not found
            return [];
        }

        // Initialize remaining balance and credit
        $remainingBalance = 0;
        $remainingCredit = 0;

        // Get all transactions for the user
        $transactions = $wallet->transactions->sortBy('updated_at');

        $results = [];
        $sum = 0;
        // Loop through each transaction
        foreach ($transactions as $transaction) {
            // Check if the transaction is valid
            if ($transaction->isValid) {
                // Calculate remaining balance and credit based on the transaction type
                if ($transaction->transaction_type === 'increaseBalance') {
                    $remainingBalance += $transaction->price;
                    $sum += $transaction->price;
                } elseif ($transaction->transaction_type === 'increaseCredit') {
                    $remainingCredit += $transaction->price;
                    $sum += $transaction->price;
                } elseif ($transaction->transaction_type === 'refund') {
                    $remainingBalance += $transaction->price;
                    $sum += $transaction->price;
                } elseif ($transaction->transaction_type === 'Withdrawal') {
                    // Check if there is enough credit to cover the withdrawal
                    if ($remainingCredit >= $transaction->price) {
                        $remainingCredit -= $transaction->price;
                    } else {
                        // If credit is not enough, deduct from balance
                        $remainingBalance -= ($transaction->price - $remainingCredit);
                        $remainingCredit = 0;
                    }
                    $sum -= $transaction->price;
                }
            }
            // Store the result for the current transaction

            $results[] = [
                'transaction_id' => $transaction->id,
                'transaction_type' => $transaction->transaction_type,
                'price' => number_format($transaction->price),
                'is_valid' => $transaction->isValid,
                'remaining_balance' => number_format($remainingBalance),
                'remaining_credit' => number_format($remainingCredit),
                'remaining_sum' => $remainingBalance + $remainingCredit,
            ];
        }

        return ['results' => $results, 'sum' => $sum];
    }


    function adminFinancialReport($filters = [])
    {
        $query = Transaction::query();

        if (isset($filters['user_id'])) {
            $query->where('wallet_id', $filters['user_id']);
        }

        if (isset($filters['is_valid'])) {
            $query->where('isValid', $filters['is_valid']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('updated_at', '>=', Carbon::parse($filters['date_from'])->format('Y-m-d'));
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('updated_at', '<=', Carbon::parse($filters['date_to'])->format('Y-m-d'));
        }

        if (isset($filters['transaction_type'])) {
            if ($filters['transaction_type'] == 'allDeposits') {
                $query->whereIn('transaction_type', ['increaseBalance', 'increaseCredit', 'refund']);
            } else {
                $query->where('transaction_type', $filters['transaction_type']);
            }
        }
        if (isset($filters['transaction_status_id'])) {
            $query->where('status_id', $filters['transaction_status_id']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        // Retrieve transactions
        $transactions = $query->orderBy('updated_at')->get();

        $adminFinancialTolerance = 0;
        $results = [];
        $sum = 0;

        // Loop through each transaction
        foreach ($transactions as $transaction) {
            // Check if the transaction is valid
            // if ($transaction->isValid || $transaction->transaction_type === 'increaseBalance') {
            // Calculate the financial impact on the admin
            $financialImpact = 0;
            if ($transaction->transaction_type === 'Withdrawal' && $transaction->isValid) {
                // Increase admin financial tolerance for valid withdrawal transactions
                $adminFinancialTolerance += $transaction->price;
                $financialImpact = $transaction->price;
                $sum += $transaction->price;
            } else if ($transaction->transaction_type === 'refund' && $transaction->isValid) {
                $adminFinancialTolerance -= $transaction->price;
                $financialImpact = $transaction->price;
                $sum -= $transaction->price;
            }
            // Store the result for the current transaction
            $results[] = [
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->wallet->user_id,
                'transaction_type' => $transaction->transaction_type,
                'price' => number_format($transaction->price),
                'is_valid' => $transaction->isValid,
                'financial_impact' => $financialImpact,
                'admin_financial_tolerance' => number_format($adminFinancialTolerance),
            ];
            // }
        }

        return ['results' => $results, 'sum' => $sum];
    }

    public function increaseWalletBalance(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000',
        ]);
        $user = Auth::user();
        $wallet = $user->wallet;
        $wallet->balance += $request->amount;
        $wallet->save();
        $transaction = Transaction::create([
            'wallet_id' => $wallet->id,
            'payment_method' => 'online',
            'price' => $request->amount,
            'status_id' => 1,
            'description' => 'افزایش موجودی کیف پول',
            'transaction_type' => 'increaseBalance',
            'isValid' => true,
        ]);
        return response()->json(['message' => 'موجودی کیف پول با موفقیت افزایش یافت'], 200);
    }

    public function grantCredit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000',
            'user_id'=> 'required|exists:users,id',
            'is_credit' => 'required',
            'description' => 'required'
        ]);
        $user = User::find($request->user_id);
        $wallet = $user->wallet;
        if(!$wallet){
            return response()->json(['message' => 'wallet not exists'], 404);
        }
        if($request->is_credit=="true"){
            $wallet->credit += $request->amount;
        }else{
            $wallet->balance += $request->amount;
        }
        $wallet->save();
        $transaction = Transaction::create([
            'wallet_id' => $wallet->id,
            'payment_method' => 'offline',
            'price' => $request->amount,
            'status_id' => 1,
            'description' => ($request->is_credit=="true"?'افزایش اعتبار ':'افزایش موجودی ').' کیف پول'.' / '.$request->description,
            'transaction_type' => $request->is_credit=="true"?'increaseBalance':'increaseCredit',
            'isValid' => true,
        ]);
        return response()->json(['message' => 'موجودی کیف پول با موفقیت افزایش یافت'], 200);
    }

    


    //write annotation

    /**
     * @OA\Get(
     *   path="/v1/wallet/usersList",
     *   tags={"Wallet"},
     *   summary="show all users that has wallet",
     *   description="only for howm has access",
     * @OA\Parameter(
     * name="searchParam",
     * description="search param",
     * in="query",
     * required=false,
     * @OA\Schema(
     * type="string"
     * )
     * ),
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
    public function userWalletsList(Request $request)
    {
        $permissions = Auth::user()->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug')->toArray();
        })->toArray();

        if (in_array('can-view-all-transactions', $permissions)) {

            $searchParam = $request->searchParam;
            //at least 3 characters
            if (strlen($searchParam) < 3) {
                $wallets = UserWallet::with('user')->whereHas('user', function ($query) use ($searchParam) {
                    $query->where('name', 'like', '%' . $searchParam . '%')
                        ->orWhere('last_name', 'like', '%' . $searchParam . '%')
                        ->orWhere('mobile', 'like', '%' . $searchParam . '%');
                })->paginate(20);
                return response()->json(['wallets' => $wallets], 200);
            }
            $wallets = UserWallet::with('user')->whereHas('user', function ($query) use ($searchParam) {
                $query->where('name', 'like', '%' . $searchParam . '%')
                    ->orWhere('last_name', 'like', '%' . $searchParam . '%')
                    ->orWhere('mobile', 'like', '%' . $searchParam . '%');
            })->paginate(20);
            return response()->json(['wallets' => $wallets], 200);
        } else {
            //error
            return response()->json(['message' => 'شما دسترسی به این بخش را ندارید'], 403);
        }
    }

    //list of wallet transation statuses

    /**
     * @OA\Get(
     *   path="/v1/wallet/transactions/status",
     *   tags={"Wallet"},
     *   summary="show all transaction statuses",
     *   description="only for howm has access",
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
    public function transactionStatuses()
    {
        $statuses = TransactionStatus::all();
        return response()->json(['statuses' => $statuses], 200);
    }

    /**
     * @OA\Get(
     *   path="/v1/wallet/transactions/export",
     *   tags={"Wallet"},
     *   summary="export all transactions",
     *   @OA\Parameter(
     *     name="user_id",
     *    description="user id",
     *    in="query",
     *   required=false,
     *   @OA\Schema(
     *  type="integer"
     *  )
     * ),
     *  @OA\Parameter(
     *     name="transaction_status_id",
     *    description="transaction status id",
     *    in="query",
     *   required=false,
     *   @OA\Schema(
     *  type="integer"
     *  )
     * ),
     *  @OA\Parameter(
     *    name="is_valid",
     *   description="is valid",
     *  in="query",
     * required=false,
     * @OA\Schema(
     * type="boolean"
     * )
     * ),
     * @OA\Parameter(
     *   name="date_from",
     * description="date from",
     * in="query",
     * required=false,
     * @OA\Schema(
     * type="string",
     * format="date"
     * )
     * ),
     * @OA\Parameter(
     *  name="date_to",
     * description="date to",
     * in="query",
     * required=false,
     * @OA\Schema(
     * type="string",
     * format="date"
     * )
     * ),
     * @OA\Parameter(
     * name="transaction_type",
     * description="transaction type",
     * in="query",
     * required=false,
     * @OA\Schema(
     * type="string",
     * enum={"increaseBalance", "increaseCredit", "Withdrawal","refund","allDeposits"}
     * )
     * ),
     * @OA\Parameter(
     * name="payment_method",
     * description="payment method",
     * in="query",
     * required=false,
     * @OA\Schema(
     * type="string",
     * enum={"online", "offline"}
     * )
     * ),
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
    public function ExportToExcel(Request $request)
    {
        $transactionsList = $this->transactionsList($request, true)->getData();

        // $transactions = $transactionsList->transactions;
        // return $transactionsList;
        return Excel::download(new ExportTransactionsList($transactionsList), 'transactions.xlsx');
    }
}
