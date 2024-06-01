<?php

namespace App\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionInitRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'buy_order' => ['required','string'],
            'session_id' => ['required','string'],
            'amount' => ['required', 'numeric'],
            'return_url' => ['required', 'string', 'url'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
