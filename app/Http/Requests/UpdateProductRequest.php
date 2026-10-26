<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cost_price' => $this->cost_price !== null ? str_replace(['.', 'Rp', ' '], '', (string) $this->cost_price) : null,
            'selling_price' => $this->selling_price !== null ? str_replace(['.', 'Rp', ' '], '', (string) $this->selling_price) : null,
            'discount_value' => $this->discount_value !== null ? str_replace(['.', 'Rp', ' '], '', (string) $this->discount_value) : null,
        ]);
    }

    public function rules(): array
    {
        $productId = $this->route('product')->id;

        return [
            'category_id' => ['required', 'exists:categories,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku,' . $productId],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode,' . $productId],
            'name' => ['required', 'string', 'max:255'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'discount_type' => ['nullable', 'string', 'in:fixed,percentage'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'boolean'],
        ];
    }
}
