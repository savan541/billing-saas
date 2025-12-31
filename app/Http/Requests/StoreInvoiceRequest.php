<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'invoice_number' => ['sometimes', 'string', 'max:255'],
            'status' => ['required', 'in:draft,sent,paid,overdue'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'Please select a client.',
            'client_id.exists' => 'Selected client is invalid.',
            'issue_date.required' => 'Issue date is required.',
            'due_date.required' => 'Due date is required.',
            'due_date.after_or_equal' => 'Due date must be after or equal to issue date.',
            'notes.max' => 'Notes cannot exceed 2000 characters.',
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
            'items.*.description.required' => 'Item description is required.',
            'items.*.description.max' => 'Item description cannot exceed 255 characters.',
            'items.*.quantity.required' => 'Item quantity is required.',
            'items.*.quantity.min' => 'Quantity must be greater than 0.',
            'items.*.quantity.max' => 'Quantity cannot exceed 999,999.99.',
            'items.*.unit_price.required' => 'Unit price is required.',
            'items.*.unit_price.min' => 'Unit price must be 0 or greater.',
            'items.*.unit_price.max' => 'Unit price cannot exceed 999,999.99.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'issue_date' => $this->issue_date ?? now()->format('Y-m-d'),
            'due_date' => $this->due_date ?? now()->addDays(30)->format('Y-m-d'),
        ]);
    }
}
