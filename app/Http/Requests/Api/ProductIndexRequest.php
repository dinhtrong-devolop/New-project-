<?php

namespace App\Http\Requests\Api;

use App\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:150'],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0', Rule::when($this->filled('min_price'), ['gte:min_price'])],
            'type' => ['nullable', Rule::enum(ProductType::class)],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'in_stock' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['featured', 'best_selling', 'latest', 'price_asc', 'price_desc', 'rating'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ];
    }
}
