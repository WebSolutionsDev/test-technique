<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku'],
            'price' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0.01',
                'max:99999999.99',
            ],
            'stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
        ];
    }
}