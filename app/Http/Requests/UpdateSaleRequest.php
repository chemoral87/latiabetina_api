<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer|exists:pos_sale_items,id',
            'items.*.product_id' => 'required|exists:pos_products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }
}
