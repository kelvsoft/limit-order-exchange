<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequest;
use App\Models\User;
use App\Models\Order;
use App\Models\Trade;
use App\Services\OrderMatchingService;
use App\Events\OrderCreated;
use App\Events\OrderMatched;
use App\Events\OrderCancelled;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected $matchingService;

    // Inject the order matching service
    public function __construct(OrderMatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    /**
     * Get user's balances and recent orders
     */
    public function index()
    {
        $user = auth()->user();

        return response()->json([
            'usd_balance' => $user->balance,
            'assets' => $user->assets()->get(['symbol', 'amount', 'locked_amount']),
            'recent_orders' => $user->orders()->latest()->limit(10)->get()
        ]);
    }

    /**
     * Return the order book (open buy and sell orders)
     */
    public function orderBook(OrderRequest $request)
    {
        $symbol = $request->symbol;

        $buyOrders = Order::where(['symbol' => $symbol, 'side' => 'buy', 'status' => 1])
            ->orderBy('price', 'desc')
            ->get();

        $sellOrders = Order::where(['symbol' => $symbol, 'side' => 'sell', 'status' => 1])
            ->orderBy('price', 'asc')
            ->get();

        return response()->json([
            'buy_orders' => $buyOrders,
            'sell_orders' => $sellOrders
        ]);
    }

    /**
     * Place a limit order with balance/asset locking, fees, and audit logging
     */
    /**
 * Place a limit order with balance/asset locking, fees, and audit logging
 */
public function store(OrderRequest $request)
{
    try {
        $order = DB::transaction(function () use ($request) {
            $symbol = $request->symbol;
            $side = $request->side;
            $price = $request->price;
            $amount = $request->amount;

            $totalValue = (float) $request->price * (float) $request->amount;
            $fee = $totalValue * 0.015; // 1.5% commission
            $totalRequired = round($totalValue + $fee, 2);

            $user = User::where('id', auth()->id())->lockForUpdate()->first();

            Log::info("Check: Balance {$user->balance} vs Required {$totalRequired}");

            if ($side === 'buy') {
                if ((float) $user->balance < $totalRequired) {
                    throw new \Exception('Insufficient USD balance for order + 1.5% commission.');
                }
                // Lock the full amount (Principal + Fee)
                $user->decrement('balance', $totalRequired);
            } else {
                // Sell side logic
                $asset = $user->assets()->where('symbol', $symbol)->lockForUpdate()->first();
                
                if (!$asset || $asset->amount < $amount) {
                    throw new \Exception('Insufficient asset amount.');
                }
                
                $asset->decrement('amount', $amount);
                $asset->increment('locked_amount', $amount);
            }

            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'symbol' => $symbol,
                'side' => $side,
                'price' => $price,
                'amount' => $amount,
                'original_amount' => $amount,
                'status' => 1,
                'fee' => $fee
            ]);

            // Broadcast event
            broadcast(new OrderCreated($order))->toOthers();

            // Trigger matching
            $this->matchingService->matchOrder($order);

            return $order;
        });

        return response()->json([
            'message' => 'Order placed successfully.',
            'order' => $order->load('user')
        ], 201);

    } catch (\Exception $e) {
        Log::error('Order placement failed: ' . $e->getMessage());
        return response()->json([
            'error' => $e->getMessage()
        ], 400);
    }
}

/**
 * Cancel an open order and release funds/assets
 */
        public function cancel($id)
        {
            try {
                $order = DB::transaction(function () use ($id) {
                    // Use the Query Builder to ensure a real DB lock
                    $user = User::where('id', auth()->id())->lockForUpdate()->first();

                    $order = Order::where('id', $id)
                        ->where('user_id', $user->id)
                        ->where('status', 1) 
                        ->lockForUpdate()
                        ->firstOrFail();

                    $order->update(['status' => 3]); // status 3 = cancelled

                    if ($order->side === 'buy') {
                        // Adding (float) and ?? 0 prevents crashes if data is missing
                        $refundAmount = ((float)$order->price * (float)$order->amount) + (float)($order->fee ?? 0);
                        $user->increment('balance', $refundAmount);
                    } else {
                        $asset = $user->assets()->where('symbol', $order->symbol)->lockForUpdate()->first();
                        if ($asset) {
                            $asset->decrement('locked_amount', $order->amount);
                            $asset->increment('amount', $order->amount);
                        }
                    }

                    broadcast(new OrderCancelled($order));

                    return $order;
                });

                return back()->with('message', 'Order cancelled successfully.');

            } catch (\Exception $e) {
                Log::error("Cancel Error: " . $e->getMessage());
                return response()->json(['error' => 'Order cancellation failed.'], 400);
            }
        }

       
}
