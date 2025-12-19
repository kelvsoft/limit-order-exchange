<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMatched implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $buy_user_id;
    public $sell_user_id;
    public $price;
    public $amount;

    public function __construct($data)
    {
        $this->buy_user_id = $data['buy_user_id'];
        $this->sell_user_id = $data['sell_user_id'];
        $this->price = $data['price'];
        $this->amount = $data['amount'];
    }

    public function broadcastOn()
    {
        return [new Channel('orders')];
    }

    public function broadcastAs()
    {
        return 'OrderMatched';
    }
}