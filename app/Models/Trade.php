<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Trade extends Model
{
    use HasFactory;

    // Trade::create() method in your Service to work
    protected $fillable = [
        'buy_order_id',
        'sell_order_id',
        'symbol',
        'price',
        'amount',
    ];

    // Good practice: Define relationships so the UI can pull user names if needed
    public function buyOrder() {
        return $this->belongsTo(Order::class, 'buy_order_id');
    }

    public function sellOrder() {
        return $this->belongsTo(Order::class, 'sell_order_id');
    }
}
