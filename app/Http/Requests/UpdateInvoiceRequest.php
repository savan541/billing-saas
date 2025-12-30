<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $invoiceId = $this->route('invoice')->id;

        return [
            'client_id' => ['required', 'exists:clients,id'],
            'number' => ['required', 'string', 'max:255', 'unique:invoices,number,' . $invoiceId],
            'status' => ['required', 'in:draft,sent,paid,overdue'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'issued_at' => ['required', 'date'],
            'due_at' => ['required', 'date', 'after_or_equal:issued_at'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'Please select a client.',
            'client_id.exists' => 'Selected client is invalid.',
            'number.required' => 'Invoice number is required.',
            'number.unique' => 'Invoice number must be unique.',
            'issued_at.required' => 'Issue date is required.',
            'due_at.after_or_equal' => 'Due date must be after or equal to issue date.',
            'items.required' => 'At least one item is required.',
            'items.*.description.required' => 'Item description is required.',
            'items.*.quantity.min' => 'Quantity must be greater than 0.',
            'items.*.unit_price.min' => 'Unit price must be 0 or greater.',
        ];
    }
}
