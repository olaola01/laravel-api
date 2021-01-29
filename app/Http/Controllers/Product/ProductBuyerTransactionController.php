<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\ApiController;
use App\Product;
use App\Seller;
use App\Transaction;
use App\Transformers\TransactionTransformer;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductBuyerTransactionController extends ApiController
{

    public function __construct()
    {
        parent::__construct();

        $this->middleware('transform.input:' . TransactionTransformer::class)->only(['store']);

        $this->middleware('scope:purchase-product')->only(['store']);

        $this->middleware('can:purchase,buyer')->only('store');

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Product $product
     * @param User $buyer
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, Product $product, User $buyer)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        if($buyer->id == $product->seller_id){
            return $this->errorResponse('The buyer must be different from the seller', 409);
        }

        if(!$buyer->isVerified()){
            return $this->errorResponse('The buyer must be a verified user', 409);
        }

        if(!$product->seller->isVerified()){
            return $this->errorResponse('The seller must be a verified user', 409);
        }

        if(!$product->isAvailable()){
            return $this->errorResponse('This Product is not available', 409);
        }

        if($product->quantity < $request->quantity){
            return $this->errorResponse('This Product does not have enough units for transaction', 409);
        }

        return DB::transaction(function () use ($request, $product, $buyer){
            $product->quantity -= $request->quantity;
            $product->save();

            $transaction = Transaction::create([
                'quantity' => $request->quantity,
                'buyer_id' => $buyer->id,
                'product_id' => $product->id,
            ]);

            return $this->showOne($transaction, 201);
        });
    }

}
