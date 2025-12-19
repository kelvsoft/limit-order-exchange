<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    // Add this protected property
    protected $fillable = [
        'user_id',
        'symbol',
        'amount',
        'locked_amount',
    ];
    
    public function user() {
    return $this->belongsTo(User::class);
    }

}
