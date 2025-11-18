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
            'receiver_username' => [
                'required',
                'string',
                'exists:users,username',
                Rule::notIn([$this->user()?->username]),
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'receiver_username.not_in' => 'You cannot transfer money to yourself.',
        ];
    }
}
