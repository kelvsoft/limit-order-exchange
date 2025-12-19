<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ensures only logged-in users can interact with these endpoints
        return auth()->check();
    }

    public function rules(): array
    {
        // 1. If we are just FETCHING the order book (GET request)
        if ($this->isMethod('get')) {
            return [
                'symbol' => 'required|string|in:BTC,ETH',
            ];
        }

        // 2. If we are PLACING an order (POST request)
        return [
            'symbol' => 'required|string|in:BTC,ETH',
            'side'   => 'required|in:buy,sell',
            // decimal:0,2 ensures price is like 95000.50
            'price'  => 'required|numeric|gt:0|decimal:0,2', 
            // decimal:0,8 ensures amount is like 0.00012345
            'amount' => 'required|numeric|gt:0|decimal:0,8',
        ];
    }

    public function messages(): array
    {
        return [
            'price.decimal'  => 'Price can have a maximum of 2 decimal places.',
            'amount.decimal' => 'Amount can have up to 8 decimal places (Satoshi precision).',
            'symbol.in'      => 'Only BTC and ETH trading pairs are available.',
            'price.gt'       => 'The price must be greater than zero.',
        ];
    }
}