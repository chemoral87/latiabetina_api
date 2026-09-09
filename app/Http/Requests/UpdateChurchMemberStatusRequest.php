<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateChurchMemberStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:ACTIVO,NO CONTESTA,NO MOLESTAR,VISITA',
            'reason' => 'nullable|string|max:1000',
        ];
    }
}
