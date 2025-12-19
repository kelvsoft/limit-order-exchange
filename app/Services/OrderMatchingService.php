<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use App\Events\OrderMatched;
use Illuminate\Support\Facades\DB;

class OrderMatchingService
{
    public function matchOrder(Order $order)
    {
        // Find the opposite side (If I buy, I need a seller)
        $oppositeSide = $order->side === 'buy' ? 'sell' : 'buy';

        DB::transaction(function () use ($order, $oppositeSide) {

       
            // Lock the incoming order first
            $order = Order::where('id', $order->id)->lockForUpdate()->first();

            //  Lock the matching orders when you query them
            $matchingOrders = Order::where('side', $oppositeSide)
                ->where('symbol', $order->symbol)
                ->where('status', 1)
                ->where(function ($query) use ($order) {
                    if ($order->side === 'buy') {
                        $query->where('price', '<=', $order->price);
                    } else {
                        $query->where('price', '>=', $order->price);
                    }
                })
                ->orderBy('price', $order->side === 'buy' ? 'asc' : 'desc')
                ->lockForUpdate() 
                ->get();

            foreach ($matchingOrders as $match) {
                if ($order->amount <= 0) break;

                // Calculate how much we can trade (the smaller of the two amounts)
                $tradeAmount = min($order->amount, $match->amount);
                $tradePrice = $match->price;

                //  Create the Trade Record
                $trade = Trade::create([
                    'buy_order_id' => $order->side === 'buy' ? $order->id : $match->id,
                    'sell_order_id' => $order->side === 'sell' ? $order->id : $match->id,
                    'symbol' => $order->symbol,
                    'price' => $tradePrice,
                    'amount' => $tradeAmount,
                ]);

                //  Update Order Amounts
                $order->decrement('amount', $tradeAmount);
                $match->decrement('amount', $tradeAmount);

                //  Close orders if they are fully filled (amount = 0)
                if ($order->amount <= 0) $order->update(['status' => 2]); // 2 = Filled
                if ($match->amount <= 0) $match->update(['status' => 2]);

                //  Update User Asset Balances (The actual exchange of BTC)
                $this->updateBalances($order, $match, $tradeAmount, $tradePrice);
            }
        });
    }

        private function updateBalances($order, $match, $amount, $price)
        {
            // Lock both users to ensure their balance updates are atomic
            $buyer = User::where('id', ($order->side === 'buy' ? $order->user_id : $match->user_id))->lockForUpdate()->first();
            $seller = User::where('id', ($order->side === 'sell' ? $order->user_id : $match->user_id))->lockForUpdate()->first();

            $totalUsd = $amount * $price;
            $seller->increment('balance', $totalUsd);

            // Lock the buyer's asset row
            $buyerAsset = $buyer->assets()->where('symbol', $order->symbol)->lockForUpdate()->first();
            if (!$buyerAsset) {
                $buyerAsset = $buyer->assets()->create(['symbol' => $order->symbol, 'amount' => 0, 'locked_amount' => 0]);
            }
            $buyerAsset->increment('amount', $amount);

            // Lock the seller's asset row
            $sellerAsset = $seller->assets()->where('symbol', $order->symbol)->lockForUpdate()->first();
            if ($sellerAsset) {
                $sellerAsset->decrement('locked_amount', $amount);
            }
            // broadcast the trade event to update UIs
          broadcast(new OrderMatched([
                'buy_user_id'  => $order->side === 'buy' ? $order->user_id : $match->user_id,
                'sell_user_id' => $order->side === 'sell' ? $order->user_id : $match->user_id,
                'price'        => $price,
                'amount'       => $amount
            ]))->toOthers();
        }
}