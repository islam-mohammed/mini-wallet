<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'receiver_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::notIn([$this->user()?->id]), // prevent sending to self
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0', // must be > 0
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'receiver_id.not_in' => 'You cannot transfer money to yourself.',
        ];
    }
}
