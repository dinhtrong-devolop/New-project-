<?php

namespace App\Http\Requests\Admin;

use App\Enums\DeliveryType;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($product)],
            'sku' => ['required', 'string', 'max:64', Rule::unique('products', 'sku')->ignore($product)],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'product_type' => ['required', Rule::enum(ProductType::class)],
            'price' => ['required', 'integer', 'min:0'],
            'original_price' => ['nullable', 'integer', 'gte:price'],
            'status' => ['required', Rule::in(array_column(ProductStatus::manageableCases(), 'value'))],
            'delivery_type' => ['required', Rule::enum(DeliveryType::class)],
            'warranty_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'is_featured' => ['boolean'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer', Rule::exists('product_images', 'id')->where('product_id', $product?->id)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: $this->input('name')),
            'sku' => Str::upper(trim((string) $this->input('sku'))),
            'is_featured' => $this->boolean('is_featured'),
            'original_price' => filled($this->input('original_price')) ? $this->input('original_price') : null,
        ]);
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $product = $this->route('product');

                if (! $product || $product->product_type->value === $this->input('product_type')) {
                    return;
                }

                if ($product->inventories()->exists()) {
                    $validator->errors()->add(
                        'product_type',
                        'Không thể đổi loại sản phẩm sau khi đã nhập dữ liệu kho.',
                    );
                }
            },
        ];
    }
}
