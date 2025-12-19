<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',        
        'symbol',
        'side',
        'price',
        'amount',
        'original_amount',
        'status',
        'fee'
    ];

    public function user() {
    return $this->belongsTo(User::class);
   }

    public function buyTrades() {
        return $this->hasMany(Trade::class, 'buy_order_id');
    }

    public function sellTrades() {
        return $this->hasMany(Trade::class, 'sell_order_id');
    }

}
