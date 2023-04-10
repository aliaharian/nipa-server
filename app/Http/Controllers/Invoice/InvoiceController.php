<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\OrderGroup;
use Illuminate\Http\Request;
use stdClass;

class InvoiceController extends Controller
{

 // show invoice
    /**
     * @OA\Get(
     *  path="/v1/invoices/{order_group_id}",
     * tags={"Invoices"},
     * summary="get a invoice by order group id",  
     * @OA\Parameter(
     *     name="order_group_id",
     *     in="path",
     *     description="id of order group",
     *     required=true,
     *     @OA\Schema(
     *         type="integer",
     *         format="int64",
     *     )
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Success",
     *    @OA\MediaType(
     *        mediaType="application/json",
     *   )
     * ),
     * security={{ "apiAuth": {} }}
     * )
     * )
     */

    public function show($order_group_id){
        // $data = request()->validate([
        //     'order_group_id' => 'required|exists:order_groups,id',
        // ]);
        $invoiceItems = array();
        $orderGroup = OrderGroup::find($order_group_id);
        if(!$orderGroup){
            return response()->json(['message'=>'order group not found'], 404);
        }
        $invoice = Invoice::updateOrCreate(['order_group_id'=>$order_group_id],['order_group_id'=>$order_group_id]);
        foreach($orderGroup->orders as $order){
            $product = $order->product;
            $width = $product->fieldValue('width');
            $length = $product->fieldValue('length');
            $area = $product->fieldValue('area');
            $invoiceItem = InvoiceItem::updateOrCreate(
                [
                    'invoice_id'=>$invoice->id,
                    'order_id'=>$order->id
                ],
                [
                    'title'=>$product->name,
                    'width'=>$width,
                    'length'=>$length,
                    'area'=>$area
                ]);

            // $item = new stdClass();
            // $item->name = $product->name;
            // $item->order_id = $order->id;
            // $item->product_id = $product->id;
            // $item->width = $width;
            // $item->length = $length;
            // $item->area = $area;
            // $item->invoice_id = $invoice->id;
            // //array push
            // array_push($invoiceItems, $item);
        }
        $invoice->items;
        return response()->json(['incoice'=>$invoice], 200);

    }

    /**
     * @OA\Post(
     *  path="/v1/invoices/{invoice_id}",
     * tags={"Invoices"},
     * summary="create a invoice item",  
     * @OA\Parameter(
     *     name="invoice_id",
     *     in="path",
     *     description="id of invoice",
     *     required=true,
     *     @OA\Schema(
     *         type="integer",
     *         format="int64",
     *     )
     * ),
       * @OA\RequestBody(
     *  required=true,
     * @OA\JsonContent(
     *  required={"title"},
     * @OA\Property(property="title", type="string", format="string", example="حمل و نقل"),
     * @OA\Property(property="price", type="number", format="number", example="100"),
     * @OA\Property(property="description", type="string", format="string", example="حمل و نقل"),
     * @OA\Property(property="off_percent", type="number", format="number", example="10"),
     * @OA\Property(property="area", type="number", format="number", example="100"),
     * @OA\Property(property="length", type="number", format="number", example="100"),
     * @OA\Property(property="width", type="number", format="number", example="100")
     * ),
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Success",
     *    @OA\MediaType(
     *        mediaType="application/json",
     *   )
     * ),
     * security={{ "apiAuth": {} }}
     * )
     * )
     */
    public function create($invoice_id , Request $request){
        //add invoice item

        $data = request()->validate([
            'title' => 'required',
            'price'=>'required',

        ]);
        $invoice = Invoice::find($invoice_id);
        if(!$invoice){
            return response()->json(['message'=>'invoice not found'], 404);
        }
        $data['invoice_id'] = $invoice_id;
        $invoiceItem = InvoiceItem::create($data);
       
        return response()->json(['incoice'=>$invoiceItem], 200);
    }
}
