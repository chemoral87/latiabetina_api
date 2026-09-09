<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreChurchMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'org_id' => 'required|exists:organizations,id',
            'conso_sheet_id' => 'sometimes|nullable|exists:conso_sheets,id',
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'second_last_name' => 'nullable|string|max:255',
            'cellphone' => 'nullable|string|max:50',
            'years_old' => 'nullable|integer|min:0|max:150',
            'number_of_children' => 'nullable|integer|min:0',
            'marriage_status' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'url_image' => 'nullable|string',
            'status' => 'nullable|in:ACTIVO,NO CONTESTA,NO MOLESTAR,VISITA',
        ];
    }
}
