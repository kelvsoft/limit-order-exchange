<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Trade; 

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Dashboard', [
            'initialBalance' => (float) $request->user()->balance,
            'initialAssets' => $request->user()->assets ?? [], // Fallback to empty array
            
            'activeOrders' => $request->user()->orders()
                ->whereIn('status', [1, 'open']) // Handles both numeric and string statuses
                ->orderBy('created_at', 'desc')
                ->get(),

            'orderBook' => [
                'asks' => Order::where('side', 'sell') 
                    ->whereIn('status', [1, 'open'])
                    ->orderBy('price', 'asc')
                    ->take(10)
                    ->get(),
                'bids' => Order::where('side', 'buy')
                    ->whereIn('status', [1, 'open'])
                    ->orderBy('price', 'desc')
                    ->take(10)
                    ->get(),
            ],

            'recentTrades' => Trade::orderBy('created_at', 'desc')
                ->take(50)
                ->get(),
        ]);
    }
}