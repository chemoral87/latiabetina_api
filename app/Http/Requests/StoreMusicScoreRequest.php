<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Validation\MusicScoreValidator;
use Illuminate\Foundation\Http\FormRequest;

final class StoreMusicScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return MusicScoreValidator::rules();
    }
}
