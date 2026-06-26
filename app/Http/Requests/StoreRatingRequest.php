<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasVerifiedEmail();
    }

    public function failedAuthorization(): never
    {
        abort(403, 'Email not verified.');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'list_id' => ['required', 'uuid', 'exists:lists,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'rating' => ['required', 'numeric', 'min:0', 'max:10'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
