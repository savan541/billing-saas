<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete', $this->client);
    }

    public function rules(): array
    {
        return [];
    }
}
