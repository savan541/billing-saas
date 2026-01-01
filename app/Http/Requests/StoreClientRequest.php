<?php

namespace App\Http\Requests;

use App\Enums\Currency;
use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:clients,email,NULL,id,user_id,' . $this->user()->id,
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'tax_id' => 'nullable|string|max:50',
            'tax_country' => 'nullable|string|max:2',
            'tax_state' => 'nullable|string|max:50',
            'tax_rate' => 'nullable|numeric|min:0|max:1',
            'tax_exempt' => 'boolean',
            'tax_exemption_reason' => 'nullable|string|max:255',
            'currency' => 'required|string|in:' . implode(',', Currency::getAll()),
        ];
    }
}
