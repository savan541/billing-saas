<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecurringInvoiceRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'frequency' => 'required|in:monthly,quarterly,yearly',
            'start_date' => 'required|date|after_or_equal:today',
            'next_run_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
