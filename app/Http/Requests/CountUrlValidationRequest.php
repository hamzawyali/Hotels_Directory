<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CountUrlValidationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'rate_from' => 'required|integer|between:1,10',
            'rate_to' => 'required|integer|between:1,10',
        ];
    }
}
